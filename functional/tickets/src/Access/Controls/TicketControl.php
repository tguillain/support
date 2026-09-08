<?php

namespace Functional\Tickets\Access\Controls;

use Functional\Tickets\Access\Perimeters\AllTicketsPerimeter;
use Functional\Tickets\Access\Perimeters\AssignedTicketsPerimeter;
use Functional\Tickets\Access\Perimeters\OwnTicketsPerimeter;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Functional\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;
use Lomkit\Access\Perimeters\Perimeter;

/**
 * Access control for tickets.
 *
 * Every perimeter reads permissions, never a role name: the permission decides
 * *which action* is allowed, the perimeter query decides *which rows*. A role
 * only exists in the seeder, as a way to group permissions.
 */
class TicketControl extends Control
{
    /**
     * The model the control refers to.
     *
     * @var class-string<Model>
     */
    protected string $model = Ticket::class;

    /**
     * @return array<Perimeter>
     */
    protected function perimeters(): array
    {
        return [
            AllTicketsPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $this->grants($user, $method, TicketsAccessSeeder::PERMISSION_VIEW_ALL))
                ->should(fn (Model $user, Model $model): bool => true)
                ->query(fn (Builder $query, Model $user): Builder => $query),

            AssignedTicketsPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $this->grants($user, $method, TicketsAccessSeeder::PERMISSION_VIEW_ASSIGNED))
                ->should(fn (Model $user, Model $model): bool => $model->assigned_technician_id === $user->getKey())
                ->query(fn (Builder $query, Model $user): Builder => $query->where('assigned_technician_id', $user->getKey())),

            OwnTicketsPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $this->grants($user, $method, TicketsAccessSeeder::PERMISSION_VIEW_OWN))
                ->should(fn (Model $user, Model $model): bool => $model->requester_id === $user->getKey())
                ->query(fn (Builder $query, Model $user): Builder => $query->where('requester_id', $user->getKey())),
        ];
    }

    /**
     * Map a Gate ability to the permission that authorizes it.
     *
     * Reading is perimeter-specific, so each perimeter passes its own view
     * permission. Writing is not: the two write permissions grant the action
     * everywhere, and the perimeter's query and `should` decide on which rows.
     */
    private function grants(Model $user, string $method, string $viewPermission): bool
    {
        return match ($method) {
            'view' => $user->can($viewPermission),
            'create' => $user->can(TicketsAccessSeeder::PERMISSION_CREATE),
            'update' => $user->can(TicketsAccessSeeder::PERMISSION_ASSIGN)
                || $user->can(TicketsAccessSeeder::PERMISSION_CLOSE),
            'delete', 'restore', 'forceDelete' => $user->can(TicketsAccessSeeder::PERMISSION_CLOSE),
            default => false,
        };
    }
}
