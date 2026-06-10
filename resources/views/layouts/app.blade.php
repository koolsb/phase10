<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Phase 10 Generator') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-full bg-zinc-50 text-zinc-800 antialiased dark:bg-zinc-950 dark:text-zinc-200">
        <header class="phase10-card-bg text-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <a href="{{ route('phases.generator') }}" class="flex items-center gap-3" wire:navigate>
                    <span class="text-2xl font-black tracking-tight">
                        Phase<span class="text-phase-yellow">10</span>
                    </span>
                    <span class="hidden text-sm font-medium text-white/70 sm:inline">Game Generator</span>
                </a>
                <flux:button href="https://en.wikipedia.org/wiki/Phase_10" target="_blank" variant="ghost" size="sm" class="text-white!">
                    Rules
                </flux:button>
            </div>
            <div class="phase10-stripe h-1.5 w-full"></div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-8">
            {{ $slot }}
        </main>

        @fluxScripts
    </body>
</html>
