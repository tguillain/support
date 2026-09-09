<div class="space-y-6">
    <a href="{{ route('tickets.index') }}" wire:navigate
       class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900">
        <span aria-hidden="true">&larr;</span>
        {{ __('tickets::form.back') }}
    </a>

    @if ($statusMessage !== null)
        <div role="status" class="rounded border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ $statusMessage }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-4">
        <div class="flex flex-col gap-1">
            <label for="title" class="text-sm font-medium text-gray-700">
                {{ __('tickets::form.fields.title') }}
            </label>
            <input id="title" type="text" wire:model="title" value="{{ $title }}" class="rounded border-gray-300">
            @error('title')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col gap-1">
            <label for="description" class="text-sm font-medium text-gray-700">
                {{ __('tickets::form.fields.description') }}
            </label>
            <textarea id="description" rows="5" wire:model="description" class="rounded border-gray-300">{{ $description }}</textarea>
            @error('description')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col gap-1">
            <label for="priority" class="text-sm font-medium text-gray-700">
                {{ __('tickets::form.fields.priority') }}
            </label>
            <select id="priority" wire:model="priority" class="rounded border-gray-300">
                @foreach ($priorities as $case)
                    <option value="{{ $case->value }}" @selected($case->value === $priority)>{{ $case->label() }}</option>
                @endforeach
            </select>
            @error('priority')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white">
            {{ __('tickets::form.buttons.save') }}
        </button>
    </form>

    @if ($ticket !== null)
        <livewire:tickets.ticket-attachments :ticket="$ticket" />

        <section class="space-y-3 border-t border-gray-200 pt-6">
            <h2 class="text-lg font-semibold">{{ __('tickets::form.assignment_heading') }}</h2>

            <p class="text-sm text-gray-600">
                {{ __('tickets::form.current_status') }} : {{ $ticket->status->label() }}
            </p>

            @error('transition')
                <p role="alert" class="rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $message }}
                </p>
            @enderror

            <div class="flex flex-wrap items-end gap-3">
                <label class="flex flex-col gap-1">
                    <span class="text-sm font-medium text-gray-700">{{ __('tickets::form.fields.technician') }}</span>
                    <select wire:model="technicianId" class="rounded border-gray-300 text-sm">
                        <option value="">{{ __('tickets::form.placeholders.technician') }}</option>
                        @foreach ($technicians as $technician)
                            <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                        @endforeach
                    </select>
                </label>

                <button type="button" wire:click="assign"
                        class="rounded border border-gray-300 px-4 py-2 text-sm font-medium">
                    {{ __('tickets::form.buttons.assign') }}
                </button>
            </div>

            @error('technicianId')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </section>
    @endif
</div>
