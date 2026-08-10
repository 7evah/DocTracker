@props([
    'activity' => null,
    'last' => false,
])

{{--
    One audit-trail entry (§34, §43 ActivityItem).

    Descriptions arrive in two shapes: Spatie's automatic events log a bare
    verb ("updated") alongside a log name, while DocumentService logs a
    namespaced key ("document.submitted"). Both are resolved here, and an
    unknown key degrades to a generic phrase rather than printing itself.
--}}

@php
    $description = $activity->description ?? '';
    $logName = $activity->log_name ?? 'document';

    $key = str_contains($description, '.')
        ? 'activity.'.$description
        : 'activity.'.$logName.'.'.$description;

    $properties = is_array($activity->properties ?? null)
        ? $activity->properties
        : ($activity->properties?->toArray() ?? []);

    $phrase = Illuminate\Support\Facades\Lang::has($key)
        ? __($key, ['revision' => $properties['revision'] ?? ''])
        : __('activity.fallback');
@endphp

<li
    {{ $attributes->class([
        'flex items-start gap-3 p-4',
        'border-b border-zinc-200 dark:border-zinc-700' => ! $last,
    ]) }}
>
    <x-user-avatar :user="$activity->causer" size="xs" class="mt-0.5 shrink-0" />

    <div class="min-w-0 flex-1">
        <p class="text-sm">
            <span class="font-medium">{{ $activity->causer?->name ?? __('Système') }}</span>
            <span class="text-zinc-600 dark:text-zinc-300">{{ $phrase }}</span>
        </p>

        <time
            datetime="{{ $activity->created_at?->toIso8601String() }}"
            class="text-xs text-zinc-500 dark:text-zinc-400"
        >
            {{ $activity->created_at?->translatedFormat('d M Y à H:i') }}
        </time>
    </div>
</li>
