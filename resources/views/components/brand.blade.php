@props([
    'href' => null,
    'compact' => false,
])

{{--
    JESA / DocFlow lockup.

    The "JESA" wordmark is a TEXT PLACEHOLDER (§15) — no official logo asset was
    supplied and the spec forbids redrawing it. To swap in the real asset later,
    replace the <span> below with an <img src="{{ asset('images/jesa-logo.svg') }}">
    and nothing else in the app needs to change.
--}}

<{{ $href ? 'a' : 'div' }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'flex items-center gap-2.5 min-w-0']) }}
>
    <span
        class="grid size-9 shrink-0 place-items-center rounded-md bg-brand-700 text-sm font-bold tracking-tight text-white dark:bg-brand-500"
        aria-hidden="true"
    >
        JESA
    </span>

    @unless ($compact)
        <span class="flex min-w-0 flex-col leading-tight">
            <span class="flex items-center gap-1.5">
                <span class="truncate text-sm font-semibold text-zinc-900 dark:text-white">
                    {{ __('common.app_name') }}
                </span>
                <span
                    class="rounded border border-amber-300 bg-amber-50 px-1 py-px text-[10px] font-medium uppercase tracking-wide text-amber-700 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-400"
                >
                    {{ __('common.prototype_badge') }}
                </span>
            </span>
            <span class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('common.app_tagline') }}
            </span>
        </span>
    @endunless

    <span class="sr-only">{{ __('common.app_name') }} — {{ __('common.prototype_notice') }}</span>
</{{ $href ? 'a' : 'div' }}>
