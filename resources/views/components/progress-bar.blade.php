@props([
    'value' => null,
    'label' => '',
    'caption' => null,
])

{{--
    Progress meter with an accessible role. The numeric caption is always
    rendered alongside the bar so progress is never conveyed by width and
    colour alone (§38).
--}}

@php $pct = $value === null ? null : max(0, min(100, (int) $value)); @endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    @if ($pct === null)
        <span class="text-sm text-zinc-400">—</span>
    @else
        <div
            role="progressbar"
            aria-valuenow="{{ $pct }}"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-label="{{ $label }}"
            class="h-1.5 w-full min-w-16 max-w-28 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700"
        >
            <div
                @class([
                    'h-full rounded-full transition-[width]',
                    'bg-green-600 dark:bg-green-500' => $pct === 100,
                    'bg-brand-600 dark:bg-brand-400' => $pct < 100,
                ])
                style="width: {{ $pct }}%"
            ></div>
        </div>

        <span class="shrink-0 text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
            {{ $caption ?? $pct.'%' }}
        </span>
    @endif
</div>
