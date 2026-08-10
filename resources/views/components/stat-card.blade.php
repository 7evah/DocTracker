@props([
    'label' => '',
    'value' => 0,
    'icon' => 'chart-bar',
    'href' => null,
    'tone' => 'neutral',
    'hint' => null,
])

{{--
    Dashboard KPI tile (§17).

    `tone` drives an accent used for the icon chip only — the number itself
    stays high-contrast neutral so the row of tiles reads as one system and
    remains legible for colour-vision deficiencies.
--}}

@php
    $tones = [
        'neutral' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
        'brand' => 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
        'info' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
        'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        'success' => 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-300',
        'danger' => 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300',
    ];

    $chip = $tones[$tone] ?? $tones['neutral'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->merge([
        'class' => 'group flex items-start gap-3 rounded-lg border border-zinc-200 bg-white p-4 transition dark:border-zinc-700 dark:bg-zinc-900'
            . ($href ? ' hover:border-brand-300 hover:shadow-sm dark:hover:border-brand-500/50' : ''),
    ]) }}
>
    <span class="grid size-9 shrink-0 place-items-center rounded-md {{ $chip }}" aria-hidden="true">
        <flux:icon :name="$icon" variant="micro" />
    </span>

    <span class="flex min-w-0 flex-col gap-0.5">
        <span class="text-2xl font-semibold leading-none tabular-nums text-zinc-900 dark:text-white">
            {{ is_numeric($value) ? number_format((float) $value, 0, ',', ' ') : $value }}
        </span>
        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $label }}</span>
        @if ($hint)
            <span class="text-xs text-zinc-500 dark:text-zinc-500">{{ $hint }}</span>
        @endif
    </span>

    @if ($href)
        <flux:icon
            name="chevron-right"
            variant="micro"
            class="ms-auto mt-1 shrink-0 text-zinc-300 transition group-hover:text-brand-600 dark:text-zinc-600"
            aria-hidden="true"
        />
    @endif
</{{ $tag }}>
