<?php

namespace Functional\Tickets\Actions;

use App\Models\User;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;

/**
 * Opens a ticket.
 *
 * Two rules live here rather than in the caller: a new ticket enters the
 * lifecycle at its initial status, and its requester is whoever opened it.
 */
class CreateTicket
{
    public function __invoke(User $requester, string $title, string $description, TicketPriority $priority): Ticket
    {
        return Ticket::create([
            'requester_id' => $requester->getKey(),
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'status' => TicketStatus::initial(),
        ]);
    }
}
