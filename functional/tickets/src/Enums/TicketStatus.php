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
        return $this->allowedTransitions() === [];
    }

    /**
     * The ticket lifecycle, as specified by the business.
     *
     * Anything absent from this table is illegal — the API must refuse it, not
     * merely the interface.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::Assigned],
            self::Assigned => [self::InProgress, self::Open],
            self::InProgress => [self::Resolved, self::Assigned],
            self::Resolved => [self::Closed, self::InProgress],
            self::Closed => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
