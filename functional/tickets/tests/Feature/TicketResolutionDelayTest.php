<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Actions\ResolveTicket;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Jobs\RecordTicketResolutionDelay;
use Functional\Tickets\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class TicketResolutionDelayTest extends TicketLifecycleTestCase
{
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
