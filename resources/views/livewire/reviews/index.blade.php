<div class="flex flex-col gap-6">

    <x-page-header :title="__('reviews.title')" :description="__('reviews.subtitle')">
        <x-slot:actions>
            @if ($this->canSeeAll())
                <flux:select wire:model.live="scope" class="w-48">
                    <flux:select.option value="mine">{{ __('reviews.filters.mine') }}</flux:select.option>
                    <flux:select.option value="all">{{ __('reviews.filters.all') }}</flux:select.option>
                </flux:select>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Quick filters double as counters, so the queue size is visible
         without reading the table (§23). --}}
    <div class="grid grid-cols-3 gap-3">
        @foreach ([
            ['key' => 'pending', 'icon' => 'clock', 'tone' => 'info'],
            ['key' => 'overdue', 'icon' => 'exclamation-triangle', 'tone' => 'danger'],
            ['key' => 'completed', 'icon' => 'check-circle', 'tone' => 'success'],
        ] as $card)
            <button type="button" wire:click="$set('filter', '{{ $filter === $card['key'] ? '' : $card['key'] }}')" class="text-start">
                <x-stat-card
                    :label="__('reviews.filters.'.$card['key'])"
                    :value="$counts[$card['key']]"
                    :icon="$card['icon']"
                    :tone="$card['tone']"
                    @class(['ring-2 ring-brand-500' => $filter === $card['key']])
                />
            </button>
        @endforeach
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <flux:select wire:model.live="priority" class="sm:w-48">
            <flux:select.option value="">{{ __('reviews.filters.priority') }}</flux:select.option>
            @foreach ($priorities as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="project" class="sm:w-56">
            <flux:select.option value="">{{ __('reviews.filters.project') }}</flux:select.option>
            @foreach ($projects as $id => $code)
                <flux:select.option :value="$id">{{ $code }}</flux:select.option>
            @endforeach
        </flux:select>

        @if ($this->hasFilters())
            <flux:button wire:click="resetFilters" variant="subtle" icon="x-mark" size="sm">
                {{ __('common.actions.reset_filters') }}
            </flux:button>
        @endif

        <flux:spacer class="max-sm:hidden" />

        <span class="text-sm text-zinc-500 max-sm:hidden dark:text-zinc-400">
            {{ trans_choice('reviews.count', $reviews->total(), ['count' => $reviews->total()]) }}
        </span>
    </div>

    @if ($reviews->isEmpty())
        <x-empty-state
            icon="eye"
            :title="$this->hasFilters() ? __('reviews.empty.filtered_title') : __('reviews.empty.title')"
            :description="$this->hasFilters() ? __('reviews.empty.filtered_description') : __('reviews.empty.description')"
        >
            @if ($this->hasFilters())
                <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="x-mark">
                    {{ __('common.actions.reset_filters') }}
                </flux:button>
            @endif
        </x-empty-state>
    @else
        {{-- Desktop table --}}
        <div class="max-lg:hidden" wire:loading.class="opacity-60">
            <x-panel :padded="false">
                {{--
                    Flux's table is table-fixed, so the column widths come from
                    this header row alone. Percentages plus w-full spread the
                    slack thinly across every column instead of pooling it into
                    one conspicuous gap, weighted by how long each column's
                    content actually runs. The owner column is conditional, so
                    each set is declared to total 100% on its own — leaving one
                    branch short would let the browser distribute the remainder
                    wherever it liked. min-width forces genuine overflow, and
                    therefore a real scrollbar via Flux's own
                    <ui-table-scroll-area> (see app.css), once the viewport is
                    too narrow for even these shares (§16, §42).
                --}}
                @php $showsOwner = $scope === 'all' && $this->canSeeAll(); @endphp

                <flux:table
                    class="w-full min-w-230
                        [&_th:first-child]:ps-4 [&_td:first-child]:ps-4
                        [&_th:last-child]:pe-4 [&_td:last-child]:pe-4"
                >
                    <flux:table.columns>
                        <flux:table.column :class="$showsOwner ? 'w-[22%]' : 'w-[26%]'">{{ __('reviews.fields.document') }}</flux:table.column>
                        <flux:table.column :class="$showsOwner ? 'w-[14%]' : 'w-[16%]'">{{ __('documents.fields.project') }}</flux:table.column>
                        @if ($showsOwner)
                            <flux:table.column class="w-[14%]">{{ __('reviews.fields.reviewer') }}</flux:table.column>
                        @endif
                        <flux:table.column :class="$showsOwner ? 'w-[10%]' : 'w-[11%]'">{{ __('reviews.fields.priority') }}</flux:table.column>
                        <flux:table.column :class="$showsOwner ? 'w-[13%]' : 'w-[15%]'">{{ __('reviews.fields.deadline') }}</flux:table.column>
                        <flux:table.column :class="$showsOwner ? 'w-[21%]' : 'w-[25%]'">{{ __('reviews.fields.status') }}</flux:table.column>
                        <flux:table.column align="end" :class="$showsOwner ? 'w-[6%]' : 'w-[8%]'"></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($reviews as $review)
                            @php $document = $review->documentVersion?->document; @endphp

                            <flux:table.row :key="$review->id">
                                <flux:table.cell>
                                    <flux:link :href="route('reviews.show', $review)" class="font-mono text-sm font-medium" wire:navigate>
                                        {{ $document?->document_number ?? '—' }}
                                    </flux:link>
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $document?->title }}
                                        · {{ __('documents.revision_label', ['revision' => $review->documentVersion?->revision]) }}
                                    </p>
                                </flux:table.cell>

                                <flux:table.cell class="truncate text-zinc-500 dark:text-zinc-400">
                                    {{ $document?->project?->project_code ?? '—' }}
                                </flux:table.cell>

                                @if ($showsOwner)
                                    <flux:table.cell class="truncate">
                                        <div class="flex items-center gap-2">
                                            <x-user-avatar :user="$review->reviewer" size="xs" class="shrink-0" />
                                            <span class="truncate">{{ $review->reviewer?->name }}</span>
                                        </div>
                                    </flux:table.cell>
                                @endif

                                <flux:table.cell>
                                    <x-badge :status="$review->priority" />
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($review->deadline)
                                        <span @class([
                                            'text-sm',
                                            'font-medium text-red-600 dark:text-red-400' => $review->isOverdue(),
                                            'text-zinc-500 dark:text-zinc-400' => ! $review->isOverdue(),
                                        ])>
                                            @if ($review->isOverdue())
                                                <flux:icon name="exclamation-triangle" variant="micro" class="inline align-text-bottom" />
                                                <span class="sr-only">{{ __('reviews.overdue') }} —</span>
                                            @endif
                                            {{ $review->deadline->translatedFormat('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-sm text-zinc-400">{{ __('reviews.no_deadline') }}</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <x-badge :status="$review->status" />
                                </flux:table.cell>

                                <flux:table.cell align="end">
                                    <flux:button
                                        :href="route('reviews.show', $review)"
                                        size="xs"
                                        variant="ghost"
                                        icon="arrow-right"
                                        wire:navigate
                                        :aria-label="__('reviews.actions.open')"
                                    />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </x-panel>
        </div>

        {{-- Mobile cards --}}
        <div class="flex flex-col gap-3 lg:hidden" wire:loading.class="opacity-60">
            @foreach ($reviews as $review)
                @php $document = $review->documentVersion?->document; @endphp

                <a
                    href="{{ route('reviews.show', $review) }}"
                    wire:navigate
                    wire:key="rev-card-{{ $review->id }}"
                    class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-white p-4 transition active:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:active:bg-zinc-800"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $document?->document_number }}
                                · {{ __('documents.revision_label', ['revision' => $review->documentVersion?->revision]) }}
                            </p>
                            <p class="mt-0.5 font-medium text-zinc-900 dark:text-white">{{ $document?->title }}</p>
                        </div>
                        <x-badge :status="$review->status" />
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge :status="$review->priority" />

                        @if ($review->deadline)
                            <span @class([
                                'text-xs',
                                'font-medium text-red-600 dark:text-red-400' => $review->isOverdue(),
                                'text-zinc-500 dark:text-zinc-400' => ! $review->isOverdue(),
                            ])>
                                {{ $review->deadline->translatedFormat('d M Y') }}
                            </span>
                        @endif

                        <span class="text-xs text-zinc-400">{{ $document?->project?->project_code }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        <flux:pagination :paginator="$reviews" />
    @endif
</div>
