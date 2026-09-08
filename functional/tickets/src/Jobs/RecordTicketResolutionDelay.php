<?php

namespace Functional\Tickets\Jobs;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Records whether the resolution met the target time carried by the priority.
 *
 * Queued afterCommit, so the row it reads is always the committed one — a job
 * picked up before the resolving transaction commits would either miss the
 * ticket entirely or read the pre-resolution state.
 */
class RecordTicketResolutionDelay implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Ticket $ticket)
    {
        /**
         * Hold the job back until the surrounding transaction has committed.
         * Set through the trait's own API rather than by redeclaring
         * $afterCommit, which Queueable already defines — a typed
         * redeclaration is an incompatible trait composition and fatals.
         */
        $this->afterCommit();
    }

    public function handle(): void
    {
        [$priorityCase, $bindings] = $this->slaHoursCase();

        DB::update(
            <<<SQL
            update `tickets`
               set `resolution_hours` = timestampdiff(hour, `created_at`, `resolved_at`),
                   `sla_met` = timestampdiff(hour, `created_at`, `resolved_at`) <= (case `priority` {$priorityCase} end)
             where `id` = ?
               and `resolved_at` is not null
            SQL,
            [...$bindings, $this->ticket->getKey()],
        );
    }

    /**
     * Build the priority → target-hours mapping as a bound SQL CASE, so the
     * comparison happens in the database instead of hydrating rows into PHP.
     *
     * @return array{0: string, 1: list<string|int>}
     */
    private function slaHoursCase(): array
    {
        $case = '';
        $bindings = [];

        foreach (TicketPriority::cases() as $priority) {
            $case .= ' when ? then ?';
            $bindings[] = $priority->value;
            $bindings[] = $priority->slaHours();
        }

        return [$case, $bindings];
    }
}
