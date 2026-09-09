<div class="flex flex-col gap-6">
    <div class="flex flex-col items-center gap-4">
        {{-- The red bar is the brand motif used on xefi.sherlox.fr (6x56, r3). --}}
        <div class="flex items-center gap-4">
            <span class="h-14 w-1.5 rounded-full bg-brand" aria-hidden="true"></span>
            <span class="text-h2 leading-none font-semibold tracking-tight text-gray-900">
                {{ config('app.name') }}
            </span>
        </div>

        <div class="flex flex-col items-center gap-2">
            <p class="text-brand text-xs font-semibold tracking-[0.08em] uppercase">
                {{ __('auth.login.eyebrow') }}
            </p>
            <h1 class="text-h2 text-center leading-tight font-semibold tracking-tight text-gray-900">
                {{ __('auth.login.heading') }}
            </h1>
            <p class="text-center text-gray-600">{{ __('auth.login.subtitle') }}</p>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <form wire:submit="authenticate" class="flex flex-col gap-6">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label for="email" class="font-medium text-gray-900">
                        {{ __('auth.login.fields.email') }}
                    </label>
                    <input id="email" type="email" name="email" autocomplete="username" required autofocus
                           wire:model="email" value="{{ $email }}"
                           @class([
                               'h-control-m rounded-field w-full border bg-white px-4 text-gray-900 placeholder:text-gray-400',
                               'border-red-600' => $errors->has('email'),
                               'border-gray-300' => ! $errors->has('email'),
                           ])>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="password" class="font-medium text-gray-900">
                        {{ __('auth.login.fields.password') }}
                    </label>
                    <input id="password" type="password" name="password" autocomplete="current-password" required
                           wire:model="password"
                           @class([
                               'h-control-m rounded-field w-full border bg-white px-4 text-gray-900',
                               'border-red-600' => $errors->has('password'),
                               'border-gray-300' => ! $errors->has('password'),
                           ])>
                </div>

                @if ($errors->any())
                    {{-- Callout in the XEFI shape: icon plus body, and a left inset
                         bar. The failure is never carried by colour alone, which
                         also keeps it distinct from the brand red. --}}
                    <p role="alert"
                       class="rounded-field flex items-start gap-2 border border-red-200 bg-red-50 p-2 text-red-800 shadow-[inset_3px_0_0_var(--color-red-600)]">
                        <svg class="size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9a1 1 0 012 0v4a1 1 0 11-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                        </svg>
                        <span>
                            <span class="font-semibold">{{ __('auth.error_label') }} :</span>
                            {{ $errors->first() }}
                        </span>
                    </p>
                @endif

                <label class="flex items-center gap-2 text-gray-700">
                    <input type="checkbox" wire:model="isRemembered"
                           class="accent-brand size-4 rounded border-gray-300">
                    <span>{{ __('auth.login.fields.remember') }}</span>
                </label>
            </div>

            {{-- The single primary action. Full width because that is what the
                 XEFI auth screen does (button { width: 100% }). --}}
            <button type="submit"
                    class="h-control-m rounded-field bg-ink w-full px-4 font-medium text-white hover:bg-ink-strong">
                {{ __('auth.login.submit') }}
            </button>
        </form>
    </div>

    <p class="flex items-center justify-center gap-2 text-xs text-gray-600">
        <svg class="size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 1a4 4 0 00-4 4v2H5a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2V9a2 2 0 00-2-2h-1V5a4 4 0 00-4-4zm-2 6V5a2 2 0 114 0v2H8z" clip-rule="evenodd" />
        </svg>
        {{ __('auth.login.secured') }}
    </p>
</div>
