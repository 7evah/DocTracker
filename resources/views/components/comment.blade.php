@props([
    'comment' => null,
    'compact' => false,
])

{{--
    A single discussion entry (§25, §43).

    Resolved state is shown with an icon and a text label, not a colour
    change alone (§38). The `actions` slot carries resolve/reply buttons.
--}}

<article {{ $attributes->class('flex gap-3') }}>
    <x-user-avatar :user="$comment->author" :size="$compact ? 'xs' : 'sm'" class="mt-0.5 shrink-0" />

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <span class="text-sm font-medium text-zinc-900 dark:text-white">
                {{ $comment->author?->name ?? __('Utilisateur supprimé') }}
            </span>

            @if ($role = $comment->author?->primaryRole())
                <flux:badge size="sm" color="zinc">{{ __('enums.role.'.$role) }}</flux:badge>
            @endif

            <time
                datetime="{{ $comment->created_at?->toIso8601String() }}"
                class="text-xs text-zinc-500 dark:text-zinc-400"
            >
                {{ $comment->created_at?->diffForHumans() }}
            </time>

            @if ($comment->page)
                <flux:badge size="sm" color="zinc">
                    {{ __('reviews.comments.page', ['page' => $comment->page]) }}
                </flux:badge>
            @endif

            @if ($comment->resolved)
                <flux:badge size="sm" color="green" icon="check-circle">
                    {{ __('reviews.comments.resolved') }}
                </flux:badge>
            @endif
        </div>

        <p @class([
            'mt-1 whitespace-pre-line text-sm leading-relaxed',
            'text-zinc-500 dark:text-zinc-400' => $comment->resolved,
            'text-zinc-700 dark:text-zinc-300' => ! $comment->resolved,
        ])>
            {{ $comment->comment }}
        </p>

        @if ($comment->resolved && $comment->resolver)
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('reviews.comments.resolved_by', ['name' => $comment->resolver->name]) }}
                · {{ $comment->resolved_at?->translatedFormat('d M Y') }}
            </p>
        @endif

        @if (trim($slot) !== '')
            <div class="mt-2 flex flex-wrap items-center gap-1">{{ $slot }}</div>
        @endif
    </div>
</article>
