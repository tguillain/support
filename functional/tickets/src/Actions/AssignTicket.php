<?php

namespace Functional\Tickets\Actions;

use App\Models\User;
use Functional\Tickets\Actions\Concerns\TransitionsTicketStatus;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Events\TicketAssigned;
use Functional\Tickets\Models\Ticket;

/**
 * Hands a ticket to a technician: Open → Assigned, and InProgress → Assigned
 * when the work is handed over to somebody else.
 */
class AssignTicket
{
    use TransitionsTicketStatus;

    public function __invoke(Ticket $ticket, User $technician): Ticket
    {
        $this->transition($ticket, TicketStatus::Assigned);

        $ticket->assigned_technician_id = $technician->getKey();
        $ticket->save();

        TicketAssigned::dispatch($ticket, $technician);

        return $ticket;
    }
}
