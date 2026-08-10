@props([
    'status' => null,
    'icon' => true,
    'size' => 'sm',
])

{{--
    Renders any BadgeEnum (DocumentStatus, Priority, UserStatus, …).

    Always pairs the colour with an icon and a text label so state is never
    communicated by colour alone (§9, §38).

    Usage: <x-badge :status="$document->status" />
--}}

@if ($status instanceof \App\Enums\Contracts\BadgeEnum)
    <flux:badge
        :color="$status->color()"
        :size="$size"
        :icon="$icon ? $status->icon() : null"
        {{ $attributes }}
    >
        {{ $status->label() }}
    </flux:badge>
@endif
