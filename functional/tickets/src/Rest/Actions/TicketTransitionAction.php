<?php

namespace Functional\Tickets\Rest\Actions;

use Functional\Tickets\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Lomkit\Rest\Actions\Action;

/**
 * Thin adapter between an API call and a transition action.
 *
 * The lifecycle rules live in the transition actions, not here: this class
 * only resolves the targets, authorizes them and opens a transaction so a
 * refused transition rolls the whole batch back. The 409 is produced by
 * IllegalTicketTransitionException reaching Laravel's handler untouched —
 * catching it here is exactly what must not happen.
 */
abstract class TicketTransitionAction extends Action
{
    /**
     * Transitions must name their targets: a classic action with no `search`
     * would apply to every ticket in the perimeter.
     *
     * @var bool
     */
    public $targeted = true;

    /**
     * @param  array<string, mixed>  $fields
     * @param  Collection<int, Ticket>  $models
     */
    public function handle(array $fields, Collection $models): void
    {
        DB::transaction(function () use ($fields, $models): void {
            foreach ($models as $ticket) {
                Gate::authorize('update', $ticket);

                $this->applyTo($ticket, $fields);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    abstract protected function applyTo(Ticket $ticket, array $fields): void;
}
