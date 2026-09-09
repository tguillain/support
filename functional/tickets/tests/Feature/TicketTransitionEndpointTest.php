<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * One pass over every transition endpoint, so no action is exposed without a
 * test walking through it.
 */
class TicketTransitionEndpointTest extends TicketLifecycleTestCase
{
    /**
     * @return array<string, array{0: string, 1: TicketStatus, 2: TicketStatus}>
     */
    public static function endpoints(): array
    {
        return [
            'assign' => ['assign-ticket', TicketStatus::Open, TicketStatus::Assigned],
            'unassign' => ['unassign-ticket', TicketStatus::Assigned, TicketStatus::Open],
            'start progress' => ['start-ticket-progress', TicketStatus::Assigned, TicketStatus::InProgress],
            'resolve' => ['resolve-ticket', TicketStatus::InProgress, TicketStatus::Resolved],
            'close' => ['close-ticket', TicketStatus::Resolved, TicketStatus::Closed],
            'reopen' => ['reopen-ticket', TicketStatus::Resolved, TicketStatus::InProgress],
        ];
    }

    #[DataProvider('endpoints')]
    public function test_each_endpoint_moves_the_ticket_where_the_table_says(string $uriKey, TicketStatus $from, TicketStatus $to): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'status' => $from,
            'assigned_technician_id' => $this->technician->getKey(),
        ]);

        $payload = ['resources' => [$ticket->getKey()]];

        if ($uriKey === 'assign-ticket') {
            $payload['fields'] = [['name' => 'technician_id', 'value' => $this->technician->getKey()]];
        }

        $this->postJson($this->action($uriKey), $payload)
            ->assertOk()
            ->assertJsonPath('data.impacted', 1);

        $this->assertSame($to, $ticket->fresh()->status);
    }

    #[DataProvider('endpoints')]
    public function test_each_endpoint_refuses_the_transition_from_a_closed_ticket(string $uriKey): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Closed,
            'assigned_technician_id' => $this->technician->getKey(),
        ]);

        $payload = ['resources' => [$ticket->getKey()]];

        if ($uriKey === 'assign-ticket') {
            $payload['fields'] = [['name' => 'technician_id', 'value' => $this->technician->getKey()]];
        }

        $this->postJson($this->action($uriKey), $payload)->assertConflict();

        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
    }
}
