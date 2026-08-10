@props([
    'title' => null,
    'icon' => null,
    'padded' => true,
])

{{-- Section container used across the dashboard and detail pages (§44). --}}

<section {{ $attributes->merge(['class' => 'flex flex-col overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900']) }}>
    @if ($title || isset($actions))
        <header class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <div class="flex min-w-0 items-center gap-2">
                @if ($icon)
                    <flux:icon :name="$icon" variant="micro" class="shrink-0 text-zinc-400" aria-hidden="true" />
                @endif

                <h2 class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $title }}</h2>
            </div>

            @isset($actions)
                <div class="flex shrink-0 items-center gap-1">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['min-w-0 flex-1', 'p-4' => $padded])>
        {{ $slot }}
    </div>
</section>
