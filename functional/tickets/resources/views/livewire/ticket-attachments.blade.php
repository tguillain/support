<section class="flex flex-col gap-4 border-t border-gray-200 pt-6">
    <h2 class="text-h3 font-semibold tracking-tight text-gray-900">
        {{ __('tickets::attachments.heading') }}
    </h2>

    @if ($statusMessage !== null)
        <p role="status" class="rounded-field border border-green-300 bg-green-50 p-2 text-green-800">
            {{ $statusMessage }}
        </p>
    @endif

    @can('update', $ticket)
        <form wire:submit="attach" class="flex flex-col gap-2">
            <label for="upload" class="font-medium text-gray-900">
                {{ __('tickets::attachments.field') }}
            </label>
            <input id="upload" type="file" wire:model="upload" class="text-gray-900">

            <p class="text-xs text-gray-600">
                {{ __('tickets::attachments.hint', ['megabytes' => (int) round($maxKilobytes / 1024)]) }}
            </p>

            @error('upload')
                <p role="alert" class="text-red-800">{{ $message }}</p>
            @enderror

            <div wire:loading wire:target="upload" class="text-xs text-gray-600">
                {{ __('tickets::attachments.uploading') }}
            </div>

            <button type="submit"
                    class="h-control-m rounded-field bg-ink w-fit px-4 font-medium text-white hover:bg-ink-strong">
                {{ __('tickets::attachments.submit') }}
            </button>
        </form>
    @endcan

    <ul class="flex flex-col gap-2">
        @forelse ($attachments as $attachment)
            <li wire:key="attachment-{{ $attachment->getKey() }}"
                class="rounded-field flex items-center justify-between gap-4 border border-gray-200 bg-white p-2">
                <span class="flex flex-col">
                    <a href="{{ route('tickets.attachments.download', $attachment) }}"
                       class="font-medium text-gray-900 underline">
                        {{ $attachment->original_name }}
                    </a>
                    <span class="text-xs text-gray-600">
                        {{ __('tickets::attachments.uploaded_by', [
                            'name' => $attachment->uploader->name,
                            'size' => round($attachment->size_bytes / 1024),
                        ]) }}
                    </span>
                </span>

                @can('delete', $attachment)
                    <button type="button" wire:click="detach({{ $attachment->getKey() }})"
                            aria-label="{{ __('tickets::attachments.detach', ['name' => $attachment->original_name]) }}"
                            class="h-control-s rounded-field border border-gray-300 px-4 font-medium text-gray-900 hover:bg-gray-50">
                        {{ __('tickets::attachments.detach_short') }}
                    </button>
                @endcan
            </li>
        @empty
            <li class="text-gray-600">{{ __('tickets::attachments.empty') }}</li>
        @endforelse
    </ul>
</section>
