<?php

namespace Functional\Tickets\Listeners;

use Functional\Tickets\Events\TicketAssigned;
use Functional\Tickets\Notifications\TicketAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued afterCommit: the assignment runs inside a transaction, and a rolled
 * back assignment must not have produced a mail.
 */
class NotifyAssignedTechnician implements ShouldQueue
{
    /** @var bool Matches Illuminate\Bus\Queueable, which declares this untyped. */
    public $afterCommit = true;

    public function handle(TicketAssigned $event): void
    {
        $event->technician->notify(new TicketAssignedNotification($event->ticket));
    }
}
