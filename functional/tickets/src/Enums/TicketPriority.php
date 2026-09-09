<?php

namespace Functional\Tickets\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return __('tickets::priorities.'.$this->value);
    }

    public function slaHours(): int
    {
        return match ($this) {
            self::Low => 72,
            self::Normal => 24,
            self::High => 8,
            self::Critical => 2,
        };
    }
}
