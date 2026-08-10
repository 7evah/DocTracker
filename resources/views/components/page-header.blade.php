@props([
    'title' => '',
    'description' => null,
])

{{-- Standard page heading. `actions` slot stacks below the title on mobile. --}}

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div class="min-w-0">
        <flux:heading size="xl" level="1" class="truncate">{{ $title }}</flux:heading>

        @if ($description)
            <flux:subheading class="mt-1">{{ $description }}</flux:subheading>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
