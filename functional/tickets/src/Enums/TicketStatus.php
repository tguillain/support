<?php

namespace Functional\Tickets\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function isTerminal(): bool
    {
        return $this === self::Closed;
    }
}
