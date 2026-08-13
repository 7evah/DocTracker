<div class="flex flex-col gap-6">

    <x-page-header
        :title="__('dashboard.greeting', ['name' => auth()->user()->name])"
        :description="__('dashboard.subtitle')"
    >
        <x-slot:actions>
            @can(\App\Support\Permissions::DOCUMENTS_CREATE)
                <flux:button icon="arrow-up-tray" variant="primary" :href="route('documents.index')" wire:navigate>
                    {{ __('common.actions.upload') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <flux:callout icon="beaker" variant="secondary">
        <flux:callout.text>{{ __('common.prototype_notice') }}</flux:callout.text>
    </flux:callout>

    {{--
        KPI row (§17). Two columns on mobile so the numbers stay readable
        without horizontal scroll, four from `lg` (§42).
    --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat-card
            :label="__('dashboard.stats.projects')"
            :value="$stats['projects']"
            icon="folder"
            tone="brand"
            :href="route('projects.index')"
        />
        <x-stat-card
            :label="__('dashboard.stats.documents')"
            :value="$stats['documents']"
            icon="document-text"
            tone="brand"
            :href="route('documents.index')"
        />
        <x-stat-card
            :label="__('dashboard.stats.pending_reviews')"
            :value="$stats['pending_reviews']"
            icon="eye"
            tone="info"
            :href="route('reviews.index')"
        />
        <x-stat-card
            :label="__('dashboard.stats.pending_approvals')"
            :value="$stats['pending_approvals']"
            icon="check-badge"
            tone="info"
            :href="route('approvals.index')"
        />
        <x-stat-card
            :label="__('dashboard.stats.approved_documents')"
            :value="$stats['approved_documents']"
            icon="check-circle"
            tone="success"
        />
        <x-stat-card
            :label="__('dashboard.stats.needs_revision')"
            :value="$stats['needs_revision']"
            icon="arrow-path"
            tone="warning"
        />
        <x-stat-card
            :label="__('dashboard.stats.overdue_reviews')"
            :value="$stats['overdue_reviews']"
            icon="clock"
            tone="danger"
        />
        <x-stat-card
            :label="__('dashboard.stats.overdue_approvals')"
            :value="$stats['overdue_approvals']"
            icon="exclamation-triangle"
            tone="danger"
        />
    </div>

    {{-- Main sections: single column on mobile, 2/3 + 1/3 split from `xl`. --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        <div class="flex flex-col gap-6 xl:col-span-2">
            <x-panel :title="__('dashboard.sections.recent_documents')" icon="document-text" :padded="false">
                <x-slot:actions>
                    <flux:button size="xs" variant="ghost" :href="route('documents.index')" wire:navigate>
                        {{ __('common.actions.view_all') }}
                    </flux:button>
                </x-slot:actions>

                @if ($recentDocuments->isEmpty())
                    <div class="p-4">
                        <x-empty-state
                            icon="document-text"
                            :title="__('dashboard.empty.documents')"
                            :description="__('dashboard.empty.documents_hint')"
                            compact
                        />
                    </div>
                @else
                    <ul class="flex flex-col">
                        @foreach ($recentDocuments as $document)
                            <li @class([
                                'flex items-center gap-3 p-4',
                                'border-b border-zinc-200 dark:border-zinc-700' => ! $loop->last,
                            ])>
                                <div class="min-w-0 flex-1">
                                    <flux:link
                                        :href="route('documents.show', $document)"
                                        class="font-mono text-sm font-medium"
                                        wire:navigate
                                    >
                                        {{ $document->document_number }}
                                    </flux:link>
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $document->title }}
                                        · {{ $document->project?->project_code }}
                                        · {{ __('documents.revision_label', ['revision' => $document->current_revision ?? '—']) }}
                                    </p>
                                </div>

                                <x-badge :status="$document->status" />

                                <span class="shrink-0 text-xs text-zinc-400 max-sm:hidden">
                                    {{ $document->updated_at->translatedFormat('d M') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-panel>

            <x-panel :title="__('dashboard.sections.pending_reviews')" icon="eye" :padded="false">
                <x-slot:actions>
                    <flux:button size="xs" variant="ghost" :href="route('reviews.index')" wire:navigate>
                        {{ __('common.actions.view_all') }}
                    </flux:button>
                </x-slot:actions>

                @if ($pendingReviews->isEmpty())
                    <div class="p-4">
                        <x-empty-state
                            icon="eye"
                            :title="__('dashboard.empty.reviews')"
                            :description="__('dashboard.empty.reviews_hint')"
                            compact
                        />
                    </div>
                @else
                    <ul class="flex flex-col">
                        @foreach ($pendingReviews as $review)
                            <li @class([
                                'flex items-center gap-3 p-4',
                                'border-b border-zinc-200 dark:border-zinc-700' => ! $loop->last,
                            ])>
                                <div class="min-w-0 flex-1">
                                    <flux:link
                                        :href="route('reviews.show', $review)"
                                        class="font-mono text-sm font-medium"
                                        wire:navigate
                                    >
                                        {{ $review->documentVersion?->document?->document_number ?? '—' }}
                                    </flux:link>
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $review->documentVersion?->document?->title }}
                                    </p>
                                </div>

                                <x-badge :status="$review->priority" />

                                @if ($review->deadline)
                                    <span @class([
                                        'shrink-0 text-xs max-sm:hidden',
                                        'font-medium text-red-600 dark:text-red-400' => $review->isOverdue(),
                                        'text-zinc-400' => ! $review->isOverdue(),
                                    ])>
                                        {{ $review->deadline->translatedFormat('d M') }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-panel>
        </div>

        <div class="flex flex-col gap-6">
            {{-- Reviews, approvals and tasks merged into one chronological list --}}
            <x-panel :title="__('dashboard.sections.upcoming_deadlines')" icon="calendar-days" :padded="false">
                @if ($deadlines->isEmpty())
                    <div class="p-4">
                        <x-empty-state
                            icon="calendar-days"
                            :title="__('dashboard.empty.deadlines')"
                            :description="__('dashboard.empty.deadlines_hint')"
                            compact
                        />
                    </div>
                @else
                    <ul class="flex flex-col">
                        @foreach ($deadlines as $item)
                            <li @class([
                                'flex items-start gap-3 p-4',
                                'border-b border-zinc-200 dark:border-zinc-700' => ! $loop->last,
                            ])>
                                <flux:icon
                                    :name="$item['icon']"
                                    variant="micro"
                                    @class([
                                        'mt-0.5 shrink-0',
                                        'text-red-600 dark:text-red-400' => $item['overdue'],
                                        'text-zinc-400' => ! $item['overdue'],
                                    ])
                                    aria-hidden="true"
                                />

                                <div class="min-w-0 flex-1">
                                    @if ($item['url'])
                                        <flux:link :href="$item['url']" class="text-sm font-medium" wire:navigate>
                                            {{ $item['label'] }}
                                        </flux:link>
                                    @else
                                        <span class="text-sm font-medium">{{ $item['label'] }}</span>
                                    @endif

                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $item['kind'] }}
                                        @if ($item['detail'])
                                            · {{ $item['detail'] }}
                                        @endif
                                    </p>
                                </div>

                                <span @class([
                                    'shrink-0 text-xs',
                                    'font-medium text-red-600 dark:text-red-400' => $item['overdue'],
                                    'text-zinc-400' => ! $item['overdue'],
                                ])>
                                    @if ($item['overdue'])
                                        <span class="sr-only">{{ __('common.labels.overdue') }} —</span>
                                    @endif
                                    {{ $item['due']->translatedFormat('d M') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-panel>

            <x-panel :title="__('dashboard.sections.recent_activity')" icon="clock" :padded="false">
                @if ($activities->isEmpty())
                    <div class="p-4">
                        <x-empty-state
                            icon="clock"
                            :title="__('dashboard.empty.activity')"
                            :description="__('dashboard.empty.activity_hint')"
                            compact
                        />
                    </div>
                @else
                    <ol class="flex flex-col">
                        @foreach ($activities as $activity)
                            <x-activity-item
                                :activity="$activity"
                                :last="$loop->last"
                                wire:key="dash-act-{{ $activity->id }}"
                            />
                        @endforeach
                    </ol>
                @endif
            </x-panel>
        </div>
    </div>
</div>
