<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Actions\AssignTicket;
use Functional\Tickets\Actions\CloseTicket;
use Functional\Tickets\Actions\ReopenTicket;
use Functional\Tickets\Actions\ResolveTicket;
use Functional\Tickets\Actions\StartTicketProgress;
use Functional\Tickets\Actions\UnassignTicket;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Events\TicketAssigned;
use Functional\Tickets\Exceptions\IllegalTicketTransitionException;
use Functional\Tickets\Jobs\RecordTicketResolutionDelay;
use Functional\Tickets\Listeners\NotifyAssignedTechnician;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Notifications\TicketAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Lomkit\Rest\Http\Requests\MutateRequest;
use Lomkit\Rest\Http\Requests\OperateRequest;
use Lomkit\Rest\Http\Requests\RestRequest;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TicketLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TicketsAccessSeeder::class);

        $this->technician = User::firstWhere('email', TicketsAccessSeeder::DEMO_TECHNICIAN_EMAIL);

        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->actingAs($manager);
    }

    private function action(string $uriKey): string
    {
        return '/api/v1/tickets/actions/'.$uriKey;
    }

    private function forgetRestRequests(): void
    {
        foreach ([RestRequest::class, OperateRequest::class, MutateRequest::class] as $request) {
            $this->app->forgetInstance($request);
        }
    }

    public function test_the_whole_happy_path_runs_through_the_actions(): void
    {
        Event::fake([TicketAssigned::class]);

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open, 'resolved_at' => null]);

        app(AssignTicket::class)($ticket, $this->technician);
        $this->assertSame(TicketStatus::Assigned, $ticket->fresh()->status);
        $this->assertSame($this->technician->getKey(), $ticket->fresh()->assigned_technician_id);

        app(StartTicketProgress::class)($ticket);
        $this->assertSame(TicketStatus::InProgress, $ticket->fresh()->status);

        app(ResolveTicket::class)($ticket);
        $this->assertSame(TicketStatus::Resolved, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->resolved_at);

        app(CloseTicket::class)($ticket);
        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
    }

    public function test_unassignment_returns_the_ticket_to_the_pool(): void
    {
        Event::fake([TicketAssigned::class]);

        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Assigned,
            'assigned_technician_id' => $this->technician->getKey(),
        ]);

        app(UnassignTicket::class)($ticket);

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->assigned_technician_id);
    }

    public function test_reopening_clears_the_resolution_date(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Resolved,
            'resolved_at' => now()->subDay(),
        ]);

        app(ReopenTicket::class)($ticket);

        $this->assertSame(TicketStatus::InProgress, $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->resolved_at);
    }

    public function test_an_illegal_transition_raises_the_named_business_exception(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $this->expectException(IllegalTicketTransitionException::class);

        app(CloseTicket::class)($ticket);
    }

    public function test_the_exception_carries_the_conflict_status_and_the_states(): void
    {
        $exception = IllegalTicketTransitionException::between(TicketStatus::Open, TicketStatus::Closed);

        $this->assertSame(Response::HTTP_CONFLICT, $exception->getStatusCode());
        $this->assertSame(TicketStatus::Open, $exception->from());
        $this->assertSame(TicketStatus::Closed, $exception->to());
    }

    public function test_a_closed_ticket_refuses_every_transition(): void
    {
        foreach ([StartTicketProgress::class, ResolveTicket::class, CloseTicket::class, ReopenTicket::class, UnassignTicket::class] as $action) {
            $ticket = Ticket::factory()->create(['status' => TicketStatus::Closed]);

            try {
                app($action)($ticket);
                $this->fail($action.' did not refuse a transition out of Closed.');
            } catch (IllegalTicketTransitionException) {
                $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
            }
        }
    }

    public function test_the_api_answers_409_on_an_illegal_transition(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $response = $this->postJson($this->action('close-ticket'), ['resources' => [$ticket->getKey()]]);

        $response->assertStatus(Response::HTTP_CONFLICT);
        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    public function test_the_api_performs_a_legal_transition(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $this->postJson($this->action('assign-ticket'), [
            'resources' => [$ticket->getKey()],
            'fields' => [['name' => 'technician_id', 'value' => $this->technician->getKey()]],
        ])->assertOk()->assertJsonPath('data.impacted', 1);

        $this->assertSame(TicketStatus::Assigned, $ticket->fresh()->status);
    }

    public function test_a_refused_transition_rolls_the_whole_batch_back(): void
    {
        Notification::fake();

        $legal = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $illegal = Ticket::factory()->create(['status' => TicketStatus::Closed]);

        $this->postJson($this->action('assign-ticket'), [
            'resources' => [$legal->getKey(), $illegal->getKey()],
            'fields' => [['name' => 'technician_id', 'value' => $this->technician->getKey()]],
        ])->assertStatus(Response::HTTP_CONFLICT);

        $this->assertSame(TicketStatus::Open, $legal->fresh()->status);
        $this->assertSame(TicketStatus::Closed, $illegal->fresh()->status);
    }

    public function test_a_transition_action_refuses_to_run_without_explicit_targets(): void
    {
        Ticket::factory()->count(3)->create(['status' => TicketStatus::Open]);

        $this->postJson($this->action('assign-ticket'), [
            'fields' => [['name' => 'technician_id', 'value' => $this->technician->getKey()]],
        ])->assertUnprocessable();

        $this->assertSame(3, Ticket::where('status', TicketStatus::Open)->count());
    }

    public function test_status_cannot_be_written_through_mutate(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $this->postJson('/api/v1/tickets/mutate', [
            'mutate' => [
                [
                    'operation' => 'update',
                    'key' => $ticket->getKey(),
                    'attributes' => ['status' => TicketStatus::Closed->value],
                ],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors('mutate.0.attributes.status');

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    public function test_a_created_ticket_always_starts_at_the_beginning_of_the_lifecycle(): void
    {
        $requester = User::factory()->create();
        $requester->assignRole('requester');
        $this->actingAs($requester);

        $created = $this->postJson('/api/v1/tickets/mutate', [
            'mutate' => [
                [
                    'operation' => 'create',
                    'attributes' => [
                        'title' => 'Imprimante hors service',
                        'description' => 'Bourrage papier permanent.',
                        'priority' => TicketPriority::High->value,
                    ],
                    'relations' => [
                        'requester' => ['operation' => 'attach', 'key' => $requester->getKey()],
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertSame(
            TicketStatus::Open,
            Ticket::findOrFail($created->json('created.0'))->status,
        );
    }

    public function test_assignment_dispatches_the_event_with_the_ticket_and_the_technician(): void
    {
        Event::fake([TicketAssigned::class]);

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        app(AssignTicket::class)($ticket, $this->technician);

        Event::assertDispatched(
            TicketAssigned::class,
            fn (TicketAssigned $event): bool => $event->ticket->is($ticket)
                && $event->technician->is($this->technician),
        );
        Event::assertListening(TicketAssigned::class, NotifyAssignedTechnician::class);
    }

    public function test_the_listener_notifies_the_assigned_technician(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        app(AssignTicket::class)($ticket, $this->technician);

        Notification::assertSentTo(
            $this->technician,
            TicketAssignedNotification::class,
            fn (TicketAssignedNotification $notification): bool => $notification->ticket->is($ticket),
        );
    }

    public function test_the_notification_body_comes_from_the_translation_files(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Open,
            'title' => 'Écran noir',
            'priority' => TicketPriority::Critical,
        ]);

        $mail = (new TicketAssignedNotification($ticket))->toMail($this->technician);

        $this->assertSame(
            __('tickets::notifications.assigned.subject', ['title' => 'Écran noir']),
            $mail->subject,
        );
        $this->assertStringContainsString('Écran noir', $mail->subject);
        $this->assertContains(
            __('tickets::notifications.assigned.sla', ['hours' => TicketPriority::Critical->slaHours()]),
            $mail->introLines,
        );
    }

    public function test_the_notification_subject_is_translated(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open, 'title' => 'Écran noir']);

        $english = (new TicketAssignedNotification($ticket))->toMail($this->technician)->subject;

        $this->app->setLocale('fr');

        $french = (new TicketAssignedNotification($ticket))->toMail($this->technician)->subject;

        $this->assertNotSame($english, $french);
        $this->assertStringContainsString('assigné', $french);
    }

    public function test_resolution_dispatches_the_delay_job_only_after_the_commit(): void
    {
        config(['queue.default' => 'database']);

        $ticket = Ticket::factory()->create(['status' => TicketStatus::InProgress]);

        DB::transaction(function () use ($ticket): void {
            app(ResolveTicket::class)($ticket);

            $this->assertSame(
                0,
                DB::table('jobs')->count(),
                'The job must not be queued while the resolving transaction is still open.',
            );
        });

        $this->assertSame(1, DB::table('jobs')->count());
    }

    public function test_the_delay_job_is_queued_on_resolution(): void
    {
        Queue::fake();

        $ticket = Ticket::factory()->create(['status' => TicketStatus::InProgress]);

        app(ResolveTicket::class)($ticket);

        Queue::assertPushed(
            RecordTicketResolutionDelay::class,
            fn (RecordTicketResolutionDelay $job): bool => $job->ticket->is($ticket),
        );
    }

    public function test_the_delay_job_records_a_met_target(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Resolved,
            'priority' => TicketPriority::Normal,
            'created_at' => now()->subHours(5),
            'resolved_at' => now(),
        ]);

        app(RecordTicketResolutionDelay::class, ['ticket' => $ticket])->handle();

        $ticket->refresh();

        $this->assertSame(5, $ticket->resolution_hours);
        $this->assertTrue((bool) $ticket->sla_met);
    }

    public function test_the_delay_job_records_a_missed_target(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Resolved,
            'priority' => TicketPriority::Critical,
            'created_at' => now()->subHours(30),
            'resolved_at' => now(),
        ]);

        app(RecordTicketResolutionDelay::class, ['ticket' => $ticket])->handle();

        $ticket->refresh();

        $this->assertSame(30, $ticket->resolution_hours);
        $this->assertFalse((bool) $ticket->sla_met);
    }

    public function test_the_delay_job_computes_in_sql_without_hydrating_rows(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Resolved,
            'created_at' => now()->subHours(2),
            'resolved_at' => now(),
        ]);

        $statements = [];
        DB::listen(function ($query) use (&$statements): void {
            $statements[] = $query->sql;
        });

        app(RecordTicketResolutionDelay::class, ['ticket' => $ticket])->handle();

        $this->assertCount(1, $statements, 'The verdict must take a single statement.');
        $this->assertStringStartsWith('update', trim($statements[0]));
        $this->assertStringContainsString('timestampdiff', $statements[0]);
    }
}
