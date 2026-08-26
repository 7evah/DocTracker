<div class="flex flex-col gap-6">

    <x-page-header :title="__('approvals.title')" :description="__('approvals.subtitle')">
        <x-slot:actions>
            @if ($this->canSeeAll())
                <flux:select wire:model.live="scope" class="w-48">
                    <flux:select.option value="mine">{{ __('approvals.filters.mine') }}</flux:select.option>
                    <flux:select.option value="all">{{ __('approvals.filters.all') }}</flux:select.option>
                </flux:select>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-3 gap-3">
        @foreach ([
            ['key' => 'pending', 'icon' => 'clock', 'tone' => 'info'],
            ['key' => 'overdue', 'icon' => 'exclamation-triangle', 'tone' => 'danger'],
            ['key' => 'completed', 'icon' => 'check-circle', 'tone' => 'success'],
        ] as $card)
            <button type="button" wire:click="$set('filter', '{{ $filter === $card['key'] ? '' : $card['key'] }}')" class="text-start">
                <x-stat-card
                    :label="__('approvals.filters.'.$card['key'])"
                    :value="$counts[$card['key']]"
                    :icon="$card['icon']"
                    :tone="$card['tone']"
                    @class(['ring-2 ring-brand-500' => $filter === $card['key']])
                />
            </button>
        @endforeach
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <flux:select wire:model.live="project" class="sm:w-56">
            <flux:select.option value="">{{ __('approvals.filters.project') }}</flux:select.option>
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
            {{ trans_choice('approvals.count', $approvals->total(), ['count' => $approvals->total()]) }}
        </span>
    </div>

    @if ($approvals->isEmpty())
        <x-empty-state
            icon="check-badge"
            :title="$this->hasFilters() ? __('approvals.empty.filtered_title') : __('approvals.empty.title')"
            :description="$this->hasFilters() ? __('approvals.empty.filtered_description') : __('approvals.empty.description')"
        >
            @if ($this->hasFilters())
                <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="x-mark">
                    {{ __('common.actions.reset_filters') }}
                </flux:button>
            @endif
        </x-empty-state>
    @else
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
                        <flux:table.column :class="$showsOwner ? 'w-[24%]' : 'w-[29%]'">{{ __('approvals.fields.document') }}</flux:table.column>
                        <flux:table.column :class="$showsOwner ? 'w-[15%]' : 'w-[17%]'">{{ __('documents.fields.project') }}</flux:table.column>
                        <flux:table.column align="center" :class="$showsOwner ? 'w-[7%]' : 'w-[8%]'">{{ __('approvals.fields.step') }}</flux:table.column>
                        @if ($showsOwner)
                            <flux:table.column class="w-[16%]">{{ __('approvals.fields.approver') }}</flux:table.column>
                        @endif
                        <flux:table.column :class="$showsOwner ? 'w-[14%]' : 'w-[16%]'">{{ __('approvals.fields.deadline') }}</flux:table.column>
                        <flux:table.column :class="$showsOwner ? 'w-[18%]' : 'w-[22%]'">{{ __('approvals.fields.status') }}</flux:table.column>
                        <flux:table.column align="end" :class="$showsOwner ? 'w-[6%]' : 'w-[8%]'"></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($approvals as $approval)
                            @php $document = $approval->documentVersion?->document; @endphp

                            <flux:table.row :key="$approval->id">
                                <flux:table.cell>
                                    <flux:link
                                        :href="route('documents.show', $document).'?tab=approvals'"
                                        class="font-mono text-sm font-medium"
                                        wire:navigate
                                    >
                                        {{ $document?->document_number ?? '—' }}
                                    </flux:link>
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $document?->title }}
                                        · {{ __('documents.revision_label', ['revision' => $approval->documentVersion?->revision]) }}
                                    </p>
                                </flux:table.cell>

                                <flux:table.cell class="truncate text-zinc-500 dark:text-zinc-400">
                                    {{ $document?->project?->project_code ?? '—' }}
                                </flux:table.cell>

                                <flux:table.cell align="center" class="tabular-nums">
                                    {{ $approval->step }}
                                </flux:table.cell>

                                @if ($showsOwner)
                                    <flux:table.cell class="truncate">
                                        @if ($approval->approver)
                                            <div class="flex items-center gap-2">
                                                <x-user-avatar :user="$approval->approver" size="xs" class="shrink-0" />
                                                <span class="truncate">{{ $approval->approver->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </flux:table.cell>
                                @endif

                                <flux:table.cell>
                                    @if ($approval->deadline)
                                        <span @class([
                                            'text-sm',
                                            'font-medium text-red-600 dark:text-red-400' => $approval->isOverdue(),
                                            'text-zinc-500 dark:text-zinc-400' => ! $approval->isOverdue(),
                                        ])>
                                            @if ($approval->isOverdue())
                                                <flux:icon name="exclamation-triangle" variant="micro" class="inline align-text-bottom" />
                                                <span class="sr-only">{{ __('approvals.overdue') }} —</span>
                                            @endif
                                            {{ $approval->deadline->translatedFormat('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-sm text-zinc-400">—</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <x-badge :status="$approval->status" />
                                </flux:table.cell>

                                <flux:table.cell align="end">
                                    <flux:button
                                        :href="route('documents.show', $document).'?tab=approvals'"
                                        size="xs"
                                        variant="ghost"
                                        icon="arrow-right"
                                        wire:navigate
                                        :aria-label="__('approvals.actions.open')"
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
            @foreach ($approvals as $approval)
                @php $document = $approval->documentVersion?->document; @endphp

                <a
                    href="{{ route('documents.show', $document) }}?tab=approvals"
                    wire:navigate
                    wire:key="appr-card-{{ $approval->id }}"
                    class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-white p-4 transition active:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:active:bg-zinc-800"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $document?->document_number }}
                                · {{ __('documents.revision_label', ['revision' => $approval->documentVersion?->revision]) }}
                            </p>
                            <p class="mt-0.5 font-medium text-zinc-900 dark:text-white">{{ $document?->title }}</p>
                        </div>
                        <x-badge :status="$approval->status" />
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <span>{{ __('approvals.fields.step') }} {{ $approval->step }}</span>
                        <span aria-hidden="true">·</span>
                        <span>{{ $document?->project?->project_code }}</span>
                        @if ($approval->deadline)
                            <span aria-hidden="true">·</span>
                            <span @class(['font-medium text-red-600 dark:text-red-400' => $approval->isOverdue()])>
                                {{ $approval->deadline->translatedFormat('d M Y') }}
                            </span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <flux:pagination :paginator="$approvals" />
    @endif
</div>
