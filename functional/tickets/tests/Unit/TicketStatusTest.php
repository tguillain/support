<?php

namespace Functional\Tickets\Tests\Unit;

use Functional\Tickets\Enums\TicketStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TicketStatusTest extends TestCase
{
    /**
     * The specification, transcribed. Every pair not listed here must be refused.
     *
     * @return array<string, array{0: TicketStatus, 1: list<TicketStatus>}>
     */
    public static function transitionTable(): array
    {
        return [
            'open' => [TicketStatus::Open, [TicketStatus::Assigned]],
            'assigned' => [TicketStatus::Assigned, [TicketStatus::InProgress, TicketStatus::Open]],
            'in progress' => [TicketStatus::InProgress, [TicketStatus::Resolved, TicketStatus::Assigned]],
            'resolved' => [TicketStatus::Resolved, [TicketStatus::Closed, TicketStatus::InProgress]],
            'closed' => [TicketStatus::Closed, []],
        ];
    }

    /**
     * @param  list<TicketStatus>  $allowed
     */
    #[DataProvider('transitionTable')]
    public function test_it_allows_exactly_the_specified_transitions(TicketStatus $from, array $allowed): void
    {
        $this->assertSame($allowed, $from->allowedTransitions());

        foreach (TicketStatus::cases() as $to) {
            $this->assertSame(
                in_array($to, $allowed, true),
                $from->canTransitionTo($to),
                sprintf('%s → %s', $from->value, $to->value),
            );
        }
    }

    public function test_a_status_never_transitions_to_itself(): void
    {
        foreach (TicketStatus::cases() as $status) {
            $this->assertFalse($status->canTransitionTo($status), $status->value);
        }
    }

    public function test_closed_is_the_only_terminal_status(): void
    {
        foreach (TicketStatus::cases() as $status) {
            $this->assertSame($status === TicketStatus::Closed, $status->isTerminal(), $status->value);
        }
    }
}
