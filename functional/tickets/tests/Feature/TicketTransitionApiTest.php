<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response;

class TicketTransitionApiTest extends TicketLifecycleTestCase
{
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
            Ticket::query()->findOrFail((int) $created->json('created.0'))->status,
        );
    }
}
