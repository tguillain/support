<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Actions\Concerns\TransitionsTicketStatus;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;

/**
 * The requester confirms: Resolved → Closed. Terminal.
 */
class CloseTicket
{
    use TransitionsTicketStatus;

    public function __invoke(Ticket $ticket): Ticket
    {
        $this->transition($ticket, TicketStatus::Closed);

        $ticket->save();

        return $ticket;
    }
}
