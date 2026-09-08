<?php

namespace Functional\Tickets\Rest\Actions;

use Functional\Tickets\Actions\ResolveTicket;
use Functional\Tickets\Models\Ticket;

class ResolveTicketAction extends TicketTransitionAction
{
    protected function applyTo(Ticket $ticket, array $fields): void
    {
        app(ResolveTicket::class)($ticket);
    }
}
