<?php

namespace Functional\Tickets\Tests\Unit;

use Functional\Tickets\Enums\TicketPriority;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The target resolution time is a property of the priority alone — no database,
 * no container — so it belongs in the Unit tier.
 */
class TicketPriorityTest extends TestCase
{
    /**
     * @return array<string, array{0: TicketPriority, 1: int}>
     */
    public static function targets(): array
    {
        return [
            'low' => [TicketPriority::Low, 72],
            'normal' => [TicketPriority::Normal, 24],
            'high' => [TicketPriority::High, 8],
            'critical' => [TicketPriority::Critical, 2],
        ];
    }

    #[DataProvider('targets')]
    public function test_each_priority_carries_its_target_in_hours(TicketPriority $priority, int $hours): void
    {
        $this->assertSame($hours, $priority->slaHours());
    }

    public function test_a_higher_priority_never_allows_more_time_than_a_lower_one(): void
    {
        $targets = array_map(
            static fn (TicketPriority $priority): int => $priority->slaHours(),
            TicketPriority::cases(),
        );

        $descending = $targets;
        rsort($descending);

        $this->assertSame($descending, $targets, 'The cases must be declared from the most permissive down.');
    }

    public function test_every_case_declares_a_positive_target(): void
    {
        foreach (TicketPriority::cases() as $priority) {
            $this->assertGreaterThan(0, $priority->slaHours(), $priority->value);
        }
    }
}
