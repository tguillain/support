<?php

namespace Functional\Tickets\Events;

use App\Models\User;
use Functional\Tickets\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketAssigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $technician,
    ) {}
}
