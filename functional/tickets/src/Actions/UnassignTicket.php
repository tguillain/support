<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Actions\Concerns\TransitionsTicketStatus;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;

/**
 * Puts a ticket back in the pool: Assigned → Open.
 */
class UnassignTicket
{
    use TransitionsTicketStatus;

    public function __invoke(Ticket $ticket): Ticket
    {
        $this->transition($ticket, TicketStatus::Open);

        $ticket->assigned_technician_id = null;
        $ticket->save();

        return $ticket;
    }
}
