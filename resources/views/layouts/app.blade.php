<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name', 'E-Rapor') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        @livewireStyles
    </head>
    <body class="bg-canvas font-sans text-body antialiased">
        {{-- The sidebar lives in the layout so every screen gets the same shell; pages
             pass their own active key through the #[Layout] attribute. --}}
        <div class="flex h-screen overflow-hidden">
            <x-app.sidebar :active="$active ?? 'dashboard'" />

            <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
                {{ $slot }}
            </div>
        </div>

        @livewireScripts
    </body>
</html>
