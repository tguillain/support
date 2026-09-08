<?php

namespace Functional\Tickets\Actions\Concerns;

use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Exceptions\IllegalTicketTransitionException;
use Functional\Tickets\Models\Ticket;

/**
 * The single gate every transition action goes through.
 *
 * It refuses rather than reports: returning a failure here would leave the
 * caller free to ignore it, and the exception handler unable to answer 409.
 */
trait TransitionsTicketStatus
{
    /**
     * @throws IllegalTicketTransitionException
     */
    protected function transition(Ticket $ticket, TicketStatus $to): void
    {
        if (! $ticket->status->canTransitionTo($to)) {
            throw IllegalTicketTransitionException::between($ticket->status, $to);
        }

        $ticket->status = $to;
    }
}
