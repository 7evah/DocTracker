@props([
    'icon' => 'inbox',
    'title' => null,
    'description' => null,
    'compact' => false,
])

{{-- Consistent empty state so no list ever renders as a blank void (§37). --}}

<div
    {{ $attributes->merge([
        'class' => 'flex flex-col items-center justify-center rounded-lg border border-dashed border-zinc-200 text-center dark:border-zinc-700 '
            . ($compact ? 'gap-2 px-4 py-8' : 'gap-3 px-6 py-12'),
    ]) }}
>
    <span
        class="grid size-10 place-items-center rounded-full bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500"
        aria-hidden="true"
    >
        <flux:icon :name="$icon" variant="micro" />
    </span>

    <div class="flex flex-col gap-1">
        <p class="text-sm font-medium text-zinc-900 dark:text-white">
            {{ $title ?? __('common.states.empty') }}
        </p>

        @if ($description)
            <p class="max-w-sm text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
        @endif
    </div>

    @if (trim($slot) !== '')
        <div class="mt-1">{{ $slot }}</div>
    @endif
</div>
