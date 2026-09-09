<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name') }}</title>

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="text-body min-h-screen bg-gray-50 font-sans text-gray-700 antialiased">
        <main class="flex min-h-screen items-center justify-center p-6">
            {{-- 26rem, the width of the XEFI auth column. --}}
            <div class="w-full max-w-[26rem]">
                {{ $slot }}
            </div>
        </main>
    </body>
</html>
