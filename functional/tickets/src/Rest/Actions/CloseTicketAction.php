<?php

namespace Functional\Tickets\Rest\Actions;

use Functional\Tickets\Actions\CloseTicket;
use Functional\Tickets\Models\Ticket;

class CloseTicketAction extends TicketTransitionAction
{
    protected function applyTo(Ticket $ticket, array $fields): void
    {
        app(CloseTicket::class)($ticket);
    }
}
