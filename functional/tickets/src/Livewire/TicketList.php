<?php

namespace Functional\Tickets\Livewire;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TicketList extends Component
{
    use WithPagination;

    private const PER_PAGE = 25;

    /**
     * Columns the client is allowed to sort on. Anything else never reaches
     * the query — a column name is not user input.
     *
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = [
        'title',
        'status',
        'priority',
        'comments_count',
        'created_at',
    ];

    #[Url]
    public string $status = '';

    #[Url]
    public string $priority = '';

    #[Url]
    public string $sortColumn = 'created_at';

    #[Url]
    public string $sortDirection = 'desc';

    /**
     * Filtering shrinks the result set, so the page the user was on may no
     * longer exist: without this they land on an empty page.
     */
    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPriority(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::SORTABLE_COLUMNS, true)) {
            return;
        }

        if ($column === $this->sortColumn) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function render(): View
    {
        return view('tickets::livewire.ticket-list', [
            'tickets' => $this->tickets(),
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
            'sortableColumns' => self::SORTABLE_COLUMNS,
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, Ticket>
     */
    private function tickets(): LengthAwarePaginator
    {
        return Ticket::controlled()
            ->with(['requester:id,name', 'assignedTechnician:id,name'])
            ->withCount('comments')
            ->when(
                $this->status !== '',
                fn ($query) => $query->where('status', $this->status),
            )
            ->when(
                $this->priority !== '',
                fn ($query) => $query->where('priority', $this->priority),
            )
            ->orderBy($this->sortedColumn(), $this->sortedDirection())
            ->paginate(self::PER_PAGE);
    }

    /**
     * The property is bound to the URL, so it is re-validated here rather than
     * trusted: a hand-edited query string must not pick the column.
     */
    private function sortedColumn(): string
    {
        return in_array($this->sortColumn, self::SORTABLE_COLUMNS, true)
            ? $this->sortColumn
            : 'created_at';
    }

    /**
     * @return 'asc'|'desc'
     */
    private function sortedDirection(): string
    {
        return $this->sortDirection === 'asc' ? 'asc' : 'desc';
    }
}
