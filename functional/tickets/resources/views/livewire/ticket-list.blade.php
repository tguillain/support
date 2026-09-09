<div class="space-y-4">
    {{-- Never offer an action the policy will refuse. --}}
    @can('create', \Functional\Tickets\Models\Ticket::class)
        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('tickets.create') }}" wire:navigate
               class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white">
                {{ __('tickets::list.create') }}
            </a>
        </div>
    @endcan

    <div class="flex flex-wrap gap-4">
        <label class="flex flex-col gap-1 text-sm">
            <span class="font-medium text-gray-700">{{ __('tickets::list.filters.status') }}</span>
            <select wire:model.live="status" class="rounded border-gray-300 text-sm">
                <option value="">{{ __('tickets::list.filters.any') }}</option>
                @foreach ($statuses as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex flex-col gap-1 text-sm">
            <span class="font-medium text-gray-700">{{ __('tickets::list.filters.priority') }}</span>
            <select wire:model.live="priority" class="rounded border-gray-300 text-sm">
                <option value="">{{ __('tickets::list.filters.any') }}</option>
                @foreach ($priorities as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    @foreach (['title', 'requester', 'technician', 'status', 'priority', 'comments_count', 'created_at'] as $column)
                        <th scope="col" class="px-3 py-2 text-left font-semibold text-gray-700">
                            @if (in_array($column, $sortableColumns, true))
                                <button type="button" wire:click="sortBy('{{ $column }}')" class="inline-flex items-center gap-1">
                                    {{ __('tickets::list.columns.' . $column) }}
                                    @if ($sortColumn === $column)
                                        <span aria-hidden="true">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                        <span class="sr-only">
                                            {{ __('tickets::list.sort.' . $sortDirection) }}
                                        </span>
                                    @endif
                                </button>
                            @else
                                {{ __('tickets::list.columns.' . $column) }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($tickets as $ticket)
                    <tr wire:key="ticket-{{ $ticket->getKey() }}">
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center gap-2">
                                {{ $ticket->title }}
                                @can('update', $ticket)
                                    {{-- Icon-only control: the label lives in sr-only + aria-label. --}}
                                    <a href="{{ route('tickets.edit', $ticket) }}" wire:navigate
                                       aria-label="{{ __('tickets::list.edit', ['title' => $ticket->title]) }}"
                                       class="text-gray-400 hover:text-gray-900">
                                        <span aria-hidden="true">&#9998;</span>
                                        <span class="sr-only">{{ __('tickets::list.edit', ['title' => $ticket->title]) }}</span>
                                    </a>
                                @endcan
                            </span>
                        </td>
                        <td class="px-3 py-2">{{ $ticket->requester->name }}</td>
                        <td class="px-3 py-2">
                            {{ $ticket->assignedTechnician?->name ?? __('tickets::list.unassigned') }}
                        </td>
                        <td class="px-3 py-2">{{ $ticket->status->label() }}</td>
                        <td class="px-3 py-2">{{ $ticket->priority->label() }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $ticket->comments_count }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ $ticket->created_at->isoFormat('LL') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-8 text-center text-gray-500">
                            {{ __('tickets::list.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $tickets->links() }}
</div>
