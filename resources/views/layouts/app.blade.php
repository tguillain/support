<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name') }}</title>

        @fonts

        {{-- Same guard as welcome.blade.php: the page still renders before a Vite build. --}}
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="text-body min-h-screen bg-gray-50 font-sans text-gray-700 antialiased">
        <header class="border-b border-gray-200 bg-white">
            <div class="h-control-l mx-auto flex max-w-7xl items-center justify-between gap-4 px-6">
                <a href="{{ route('tickets.index') }}" wire:navigate class="flex items-center gap-2">
                    <span class="h-6 w-1.5 rounded-full bg-brand" aria-hidden="true"></span>
                    <span class="text-h4 font-semibold tracking-tight text-gray-900">
                        {{ config('app.name') }}
                    </span>
                </a>

                @auth
                    <div class="flex items-center gap-4">
                        <span class="text-gray-600">
                            {{ __('auth.signed_in_as') }}
                            <span class="font-medium text-gray-900">{{ auth()->user()->name }}</span>
                        </span>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="h-control-s rounded-field border border-gray-300 bg-white px-4 font-medium text-gray-900 hover:bg-gray-50">
                                {{ __('auth.logout') }}
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </header>

        {{-- Header and content are distinct page zones: 3xl (48px). --}}
        <main class="mx-auto max-w-7xl px-6 pt-12 pb-12">
            @isset($title)
                <h1 class="text-h1 mb-6 leading-tight font-semibold tracking-tight text-gray-900">{{ $title }}</h1>
            @endisset

            {{ $slot }}
        </main>
    </body>
</html>
