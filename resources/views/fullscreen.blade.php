<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <title>{{ $title }}</title>

        @vite(['resources/css/app.css'])
        @fluxAppearance

        <style>
            body {
                overscroll-behavior: none;
                touch-action: manipulation;
                -webkit-tap-highlight-color: transparent;
            }
        </style>
    </head>
    <body class="bg-phase-blue-deep h-full overflow-hidden">

        {{-- Floating controls --}}
        <div class="fixed top-0 right-0 left-0 z-50 flex items-center justify-between px-5 py-4">
            <a
                href="{{ $backUrl }}"
                class="flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-medium text-white/70 backdrop-blur-sm transition hover:bg-white/20 hover:text-white"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back
            </a>

            <div class="flex items-center gap-2">
                <button
                    id="wake-btn"
                    type="button"
                    class="flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-medium text-white/70 backdrop-blur-sm transition hover:bg-white/20 hover:text-white"
                    title="Toggle keep-awake"
                >
                    {{-- Moon — shown when wake lock is OFF --}}
                    <svg id="wake-icon-off" xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 26 26" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75 9.75 9.75 0 0 1 8.25 6a9.72 9.72 0 0 1 .752-3.752 9.75 9.75 0 0 0-10.978 10.44 9.75 9.75 0 0 0 10.296 8.802A9.754 9.754 0 0 0 21.752 15.002Z" />
                    </svg>
                    {{-- Sun — shown when wake lock is ON --}}
                    <svg id="wake-icon-on" xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 26 26" fill="none" stroke="currentColor" stroke-width="2" class="hidden h-5 w-5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    </svg>
                    <span id="wake-label">Keep awake</span>
                </button>
            </div>
        </div>

        {{-- Card --}}
        <div class="flex h-full items-center justify-center overflow-y-auto px-3 pb-4 pt-16 sm:px-6 sm:pb-6 sm:pt-20">
            @if (count($phases))
                <div class="w-full max-w-4xl">
                    <x-phase-card :phases="$phases" :title="$title" :show-bands="true" size="xl" />
                </div>
            @else
                <div class="rounded-xl border border-white/20 px-8 py-16 text-center text-white/50">
                    No phases to display. Head back and generate a game first.
                </div>
            @endif
        </div>

        <script>
            (() => {
                const btn      = document.getElementById('wake-btn');
                const iconOff  = document.getElementById('wake-icon-off');
                const iconOn   = document.getElementById('wake-icon-on');
                const label    = document.getElementById('wake-label');

                let wakeLock = null;
                let userWantsAwake = false;

                const supported = 'wakeLock' in navigator;

                if (!supported) {
                    btn.title = 'Screen wake lock is not supported in this browser';
                    btn.style.opacity = '0.4';
                    btn.style.cursor = 'not-allowed';
                }

                function setActive(active) {
                    if (active) {
                        iconOff.classList.add('hidden');
                        iconOn.classList.remove('hidden');
                        label.textContent = 'Screen awake';
                        btn.classList.replace('text-white/70', 'text-white');
                        btn.classList.replace('bg-white/10', 'bg-white/25');
                    } else {
                        iconOff.classList.remove('hidden');
                        iconOn.classList.add('hidden');
                        label.textContent = 'Keep awake';
                        btn.classList.replace('text-white', 'text-white/70');
                        btn.classList.replace('bg-white/25', 'bg-white/10');
                    }
                }

                async function requestWakeLock() {
                    try {
                        wakeLock = await navigator.wakeLock.request('screen');
                        wakeLock.addEventListener('release', () => {
                            wakeLock = null;
                            if (!userWantsAwake) setActive(false);
                        });
                        setActive(true);
                    } catch (e) {
                        userWantsAwake = false;
                        setActive(false);
                    }
                }

                async function toggleWakeLock() {
                    if (!supported) return;
                    userWantsAwake = !userWantsAwake;
                    if (userWantsAwake) {
                        await requestWakeLock();
                    } else {
                        await wakeLock?.release();
                        wakeLock = null;
                        setActive(false);
                    }
                }

                // Re-acquire after switching tabs / unlocking device.
                document.addEventListener('visibilitychange', async () => {
                    if (document.visibilityState === 'visible' && userWantsAwake && !wakeLock) {
                        await requestWakeLock();
                    }
                });

                btn.addEventListener('click', toggleWakeLock);
            })();
        </script>
    </body>
</html>
