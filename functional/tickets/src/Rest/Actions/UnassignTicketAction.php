<?php

namespace Functional\Tickets\Rest\Actions;

use Functional\Tickets\Actions\UnassignTicket;
use Functional\Tickets\Models\Ticket;

class UnassignTicketAction extends TicketTransitionAction
{
    protected function applyTo(Ticket $ticket, array $fields): void
    {
        app(UnassignTicket::class)($ticket);
    }
}
