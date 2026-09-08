<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Actions\Concerns\TransitionsTicketStatus;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;

/**
 * The technician picks the ticket up: Assigned → InProgress.
 */
class StartTicketProgress
{
    use TransitionsTicketStatus;

    public function __invoke(Ticket $ticket): Ticket
    {
        $this->transition($ticket, TicketStatus::InProgress);

        $ticket->save();

        return $ticket;
    }
}
