<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Actions\Concerns\TransitionsTicketStatus;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;

/**
 * The fix did not hold: Resolved → InProgress.
 */
class ReopenTicket
{
    use TransitionsTicketStatus;

    public function __invoke(Ticket $ticket): Ticket
    {
        $this->transition($ticket, TicketStatus::InProgress);

        $ticket->resolved_at = null;
        $ticket->save();

        return $ticket;
    }
}
