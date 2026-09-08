<?php

namespace Functional\Tickets\Rest\Actions;

use App\Models\User;
use Functional\Tickets\Actions\AssignTicket;
use Functional\Tickets\Models\Ticket;
use Lomkit\Rest\Http\Requests\RestRequest;

class AssignTicketAction extends TicketTransitionAction
{
    /**
     * @return array<string, mixed>
     */
    public function fields(RestRequest $request): array
    {
        return [
            'technician_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    protected function applyTo(Ticket $ticket, array $fields): void
    {
        app(AssignTicket::class)($ticket, User::findOrFail($fields['technician_id']));
    }
}
