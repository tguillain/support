<?php

namespace Functional\Tickets\Rest\Actions;

use Functional\Tickets\Actions\StartTicketProgress;
use Functional\Tickets\Models\Ticket;

class StartTicketProgressAction extends TicketTransitionAction
{
    protected function applyTo(Ticket $ticket, array $fields): void
    {
        app(StartTicketProgress::class)($ticket);
    }
}
