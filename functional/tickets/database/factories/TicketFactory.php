<?php

namespace Functional\Tickets\Database\Factories;

use App\Models\User;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    private const PRUNABLE_SINCE_DAYS = 120;

    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'assigned_technician_id' => null,
            'title' => faker()->words(6),
            'description' => faker()->paragraphs(2),
            'status' => faker()->randomElement(TicketStatus::cases()),
            'priority' => faker()->randomElement(TicketPriority::cases()),
            'resolved_at' => null,
        ];
    }

    public function assignedTo(?User $technician = null): static
    {
        return $this->state(fn (): array => [
            'assigned_technician_id' => $technician ?? User::factory(),
        ]);
    }

    public function withPriority(TicketPriority $priority): static
    {
        return $this->state(fn (): array => [
            'priority' => $priority,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(function (array $attributes): array {
            $createdAt = faker()->dateTime(fromTimestamp: '-3 months', toTimestamp: '-1 day');

            return [
                'status' => TicketStatus::Resolved,
                'assigned_technician_id' => User::factory(),
                'created_at' => $createdAt,
                'resolved_at' => (clone $createdAt)->modify('+'.faker()->number(1, 240).' hours'),
            ];
        });
    }

    public function prunable(): static
    {
        return $this->state(fn (): array => [
            'deleted_at' => now()->subDays(self::PRUNABLE_SINCE_DAYS),
        ]);
    }
}
