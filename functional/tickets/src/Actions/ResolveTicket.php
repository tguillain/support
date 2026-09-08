<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Actions\Concerns\TransitionsTicketStatus;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Jobs\RecordTicketResolutionDelay;
use Functional\Tickets\Models\Ticket;

/**
 * The work is done: InProgress → Resolved.
 */
class ResolveTicket
{
    use TransitionsTicketStatus;

    public function __invoke(Ticket $ticket): Ticket
    {
        $this->transition($ticket, TicketStatus::Resolved);

        $ticket->resolved_at = now();
        $ticket->save();

        /** The job is queued afterCommit, so it can never read the pre-commit row. */
        RecordTicketResolutionDelay::dispatch($ticket);

        return $ticket;
    }
}
