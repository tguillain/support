<?php

namespace Functional\Tickets\Http\Controllers;

use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Exceptions\TicketExportFailedException;
use Functional\Tickets\Models\Ticket;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV export of the tickets resolved during the current month.
 *
 * This endpoint stays a plain controller because it does not expose a
 * resource: it returns a flat, denormalised report — one derived column
 * computed in SQL, no relations, no pagination, no mutation — for which a
 * Resource has nothing to declare.
 */
class TicketsExportController
{
    /**
     * @var list<string>
     */
    private const COLUMNS = [
        'id',
        'title',
        'priority',
        'requester',
        'technician',
        'created_at',
        'resolved_at',
        'resolution_hours',
    ];

    public function __invoke(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Ticket::class);

        $filename = sprintf('tickets-resolved-%s.csv', now()->format('Y-m'));

        return response()->streamDownload(
            function () use ($request): void {
                $csv = fopen('php://output', 'wb');

                if ($csv === false) {
                    throw TicketExportFailedException::streamUnavailable();
                }

                fputcsv($csv, self::COLUMNS);

                foreach ($this->resolvedThisMonth($request)->cursor() as $row) {
                    fputcsv($csv, (array) $row);
                }

                fclose($csv);
            },
            $filename,
            ['Content-Type' => 'text/csv'],
        );
    }

    /**
     * The join, the derived resolution time and the month bounds are all
     * resolved by the database; rows are streamed one at a time so memory
     * stays flat whatever the volume.
     */
    private function resolvedThisMonth(Request $request): Builder
    {
        $month = $request->date('month', 'Y-m') ?? now();

        return Ticket::query()
            ->toBase()
            ->select([
                'tickets.id',
                'tickets.title',
                'tickets.priority',
                'requesters.name as requester',
                'technicians.name as technician',
                'tickets.created_at',
                'tickets.resolved_at',
            ])
            ->selectRaw('TIMESTAMPDIFF(HOUR, tickets.created_at, tickets.resolved_at) as resolution_hours')
            ->join('users as requesters', 'requesters.id', '=', 'tickets.requester_id')
            ->leftJoin('users as technicians', 'technicians.id', '=', 'tickets.assigned_technician_id')
            ->whereNull('tickets.deleted_at')
            ->whereIn('tickets.status', [TicketStatus::Resolved->value, TicketStatus::Closed->value])
            ->whereNotNull('tickets.resolved_at')
            ->whereBetween('tickets.resolved_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
            ])
            ->orderBy('tickets.resolved_at');
    }
}
