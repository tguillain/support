<?php

namespace Functional\Tickets\Rest\Actions;

use Functional\Tickets\Actions\ReopenTicket;
use Functional\Tickets\Models\Ticket;

class ReopenTicketAction extends TicketTransitionAction
{
    protected function applyTo(Ticket $ticket, array $fields): void
    {
        app(ReopenTicket::class)($ticket);
    }
}
