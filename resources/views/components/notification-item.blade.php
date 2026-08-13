@props([
    'notification' => null,
    'compact' => false,
    'last' => false,
])

{{--
    One notification line (§26, §43). Shared by the header bell and the
    notification centre so both read identically.

    Unread is marked with a dot AND a heavier weight, not colour alone (§38).
    Notification payloads are written by our own Notification classes, but
    every field is still defaulted here — a payload written by an older
    release should degrade rather than blow up the page.
--}}

@php
    $data = $notification->data ?? [];
    $unread = $notification->read_at === null;
@endphp

<li
    {{ $attributes->class([
        'flex items-start gap-3',
        'px-3 py-2.5' => $compact,
        'p-4' => ! $compact,
        'border-b border-zinc-200 dark:border-zinc-700' => ! $last,
        'bg-brand-50/40 dark:bg-brand-500/5' => $unread,
    ]) }}
>
    <span
        @class([
            'mt-0.5 grid shrink-0 place-items-center rounded-full',
            'size-7' => $compact,
            'size-9' => ! $compact,
            match ($data['color'] ?? 'zinc') {
                'green' => 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-300',
                'red' => 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300',
                'amber' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
                'sky' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
                default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
            },
        ])
        aria-hidden="true"
    >
        <flux:icon :name="$data['icon'] ?? 'bell'" variant="micro" />
    </span>

    <div class="min-w-0 flex-1">
        <p @class([
            'text-sm',
            'font-medium text-zinc-900 dark:text-white' => $unread,
            'text-zinc-600 dark:text-zinc-300' => ! $unread,
        ])>
            {{ $data['message'] ?? __('notifications.title') }}
        </p>

        <p class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-zinc-500 dark:text-zinc-400">
            <time datetime="{{ $notification->created_at?->toIso8601String() }}">
                {{ $notification->created_at?->diffForHumans() }}
            </time>

            @if ($unread)
                <span class="flex items-center gap-1">
                    <span class="size-1.5 rounded-full bg-brand-600 dark:bg-brand-400" aria-hidden="true"></span>
                    {{ __('notifications.unread') }}
                </span>
            @endif
        </p>

        @if (trim($slot) !== '')
            <div class="mt-2 flex flex-wrap items-center gap-1">{{ $slot }}</div>
        @endif
    </div>
</li>
