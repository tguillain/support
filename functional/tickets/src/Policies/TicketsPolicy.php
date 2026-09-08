<?php

namespace Functional\Tickets\Policies;

use App\Models\User;
use Functional\Tickets\Access\Controls\TicketControl;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Functional\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;
use Lomkit\Access\Policies\ControlledPolicy;

/**
 * Every ability delegates to TicketControl, so the row-level rules live in one
 * place and the API, the policy and the Gate can never disagree.
 *
 * Only the relation abilities are declared here: the package has no perimeter
 * equivalent for attach/detach.
 */
class TicketsPolicy extends ControlledPolicy
{
    /**
     * @var class-string<Control>
     */
    protected string $control = TicketControl::class;

    /**
     * Determine whether the user can attach a requester or a technician.
     */
    public function attachUser(Model $user, Ticket $ticket, User $attached): bool
    {
        return $user->can(TicketsAccessSeeder::PERMISSION_CREATE)
            || $user->can(TicketsAccessSeeder::PERMISSION_ASSIGN);
    }

    /**
     * Determine whether the user can detach a requester or a technician.
     */
    public function detachUser(Model $user, Ticket $ticket, User $detached): bool
    {
        return $user->can(TicketsAccessSeeder::PERMISSION_ASSIGN);
    }
}
