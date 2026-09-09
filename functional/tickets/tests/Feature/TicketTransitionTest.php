<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Actions\AssignTicket;
use Functional\Tickets\Actions\CloseTicket;
use Functional\Tickets\Actions\ReopenTicket;
use Functional\Tickets\Actions\ResolveTicket;
use Functional\Tickets\Actions\StartTicketProgress;
use Functional\Tickets\Actions\UnassignTicket;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Events\TicketAssigned;
use Functional\Tickets\Exceptions\IllegalTicketTransitionException;
use Functional\Tickets\Models\Ticket;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

class TicketTransitionTest extends TicketLifecycleTestCase
{
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

    /**
     * Closed is terminal, so every action must refuse it.
     *
     * @return array<string, array{0: class-string}>
     */
    public static function transitionsOutOfClosed(): array
    {
        return [
            'start progress' => [StartTicketProgress::class],
            'resolve' => [ResolveTicket::class],
            'close' => [CloseTicket::class],
            'reopen' => [ReopenTicket::class],
            'unassign' => [UnassignTicket::class],
        ];
    }

    /**
     * @param  class-string  $action
     */
    #[DataProvider('transitionsOutOfClosed')]
    public function test_a_closed_ticket_refuses_every_transition(string $action): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Closed]);

        $this->expectException(IllegalTicketTransitionException::class);

        app($action)($ticket);
    }
}
