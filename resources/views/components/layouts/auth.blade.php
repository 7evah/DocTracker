@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-full bg-surface font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-white">

    {{--
        Split layout: brand panel on the left from `lg`, form on the right.
        Below `lg` the panel collapses to a compact header so the form is
        immediately reachable on a phone without scrolling (§16).
    --}}
    <div class="flex min-h-dvh flex-col lg:flex-row">

        <aside class="relative flex shrink-0 flex-col justify-between overflow-hidden bg-brand-700 px-6 py-8 text-white lg:w-[42%] lg:px-12 lg:py-12">
            {{-- Blueprint-grid texture: subtle, technical, no gradients (§15). --}}
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.12]"
                style="background-image:linear-gradient(to right,#fff 1px,transparent 1px),linear-gradient(to bottom,#fff 1px,transparent 1px);background-size:32px 32px;"
                aria-hidden="true"
            ></div>

            <div class="relative flex items-center gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-md bg-white text-sm font-bold tracking-tight text-brand-700">
                    JESA
                </span>
                <span class="flex flex-col leading-tight">
                    <span class="text-base font-semibold">{{ __('common.app_name') }}</span>
                    <span class="text-xs text-white/70">{{ __('common.app_tagline') }}</span>
                </span>
            </div>

            <div class="relative mt-10 hidden lg:block">
                <p class="text-2xl font-semibold leading-snug">
                    {{ __('Une source unique de vérité pour vos documents techniques.') }}
                </p>
                <ul class="mt-6 flex flex-col gap-3 text-sm text-white/80">
                    @foreach ([
                        __('Révisions tracées, jamais écrasées'),
                        __('Circuits de revue et d’approbation configurables'),
                        __('Historique complet et auditable'),
                    ] as $point)
                        <li class="flex items-start gap-2">
                            <flux:icon name="check-circle" variant="micro" class="mt-0.5 shrink-0" aria-hidden="true" />
                            <span>{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative mt-8 hidden text-xs text-white/60 lg:block">
                {{ __('common.prototype_notice') }}
            </p>
        </aside>

        <main class="flex flex-1 items-center justify-center px-6 py-10 lg:px-12">
            <div class="w-full max-w-sm">
                {{ $slot }}

                <p class="mt-8 text-center text-xs text-zinc-500 lg:hidden dark:text-zinc-400">
                    {{ __('common.prototype_notice') }}
                </p>
            </div>
        </main>
    </div>

    {{-- Flux ships its interactive behaviour here. Without it the password
         field's reveal toggle throws "fluxInputViewable is not defined" on
         every login page load, and any Flux control on an auth screen is
         inert — the app layout has always had it, this one was missed. --}}
    @fluxScripts
</body>
</html>
