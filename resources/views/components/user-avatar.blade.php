@props([
    'user' => null,
    'size' => 'sm',
    'tooltip' => false,
])

{{--
    Avatar with initials fallback. Flux renders <flux:avatar> with a colour
    derived from the name, which keeps a wall of avatars distinguishable.
--}}

@php
    $name = $user?->name ?? '—';
    $url = $user?->avatarUrl();
@endphp

<flux:avatar
    :name="$name"
    :src="$url"
    :initials="$user?->initials()"
    :size="$size"
    color="auto"
    circle
    :tooltip="$tooltip ? $name : null"
    {{ $attributes }}
/>
