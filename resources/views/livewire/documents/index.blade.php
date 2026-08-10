<div class="flex flex-col gap-6">

    <x-page-header :title="__('documents.title')" :description="__('documents.subtitle')">
        <x-slot:actions>
            @can('create', App\Models\Document::class)
                <flux:button icon="arrow-up-tray" variant="primary" :href="route('documents.create')" wire:navigate>
                    {{ __('documents.create') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- Search is always visible; the wider filter set collapses on mobile
         so the list itself stays above the fold (§42). --}}
    <div class="flex flex-col gap-3">
        <div class="flex items-center gap-2">
            <flux:input
                wire:model.live.debounce.300ms="search"
                :placeholder="__('documents.filters.search')"
                icon="magnifying-glass"
                clearable
                class="flex-1"
                :label="__('common.actions.search')"
                label:class="sr-only"
            />

            <flux:button
                wire:click="$toggle('showFilters')"
                icon="funnel"
                :variant="$this->hasFilters() ? 'primary' : 'outline'"
                class="shrink-0"
            >
                <span class="max-sm:hidden">{{ __('common.actions.filter') }}</span>
            </flux:button>
        </div>

        <div x-show="$wire.showFilters || {{ $this->hasFilters() ? 'true' : 'false' }}" x-collapse x-cloak>
            <x-panel>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <flux:select wire:model.live="project" :label="__('documents.fields.project')">
                        <flux:select.option value="">{{ __('documents.filters.project') }}</flux:select.option>
                        @foreach ($projects as $id => $code)
                            <flux:select.option :value="$id">{{ $code }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="discipline" :label="__('documents.fields.discipline')">
                        <flux:select.option value="">{{ __('documents.filters.discipline') }}</flux:select.option>
                        @foreach ($disciplines as $id => $label)
                            <flux:select.option :value="$id">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="status" :label="__('documents.fields.status')">
                        <flux:select.option value="">{{ __('documents.filters.status') }}</flux:select.option>
                        @foreach ($statuses as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="creator" :label="__('documents.fields.creator')">
                        <flux:select.option value="">{{ __('documents.filters.creator') }}</flux:select.option>
                        @foreach ($creators as $id => $name)
                            <flux:select.option :value="$id">{{ $name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model.live="from" type="date" :label="__('documents.filters.from')" />

                    <flux:input wire:model.live="to" type="date" :label="__('documents.filters.to')" />

                    <flux:select wire:model.live="sort" :label="__('projects.filters.sort')">
                        @foreach (['latest', 'oldest', 'number', 'title', 'status'] as $option)
                            <flux:select.option :value="$option">{{ __('documents.sort.'.$option) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                @if ($this->hasFilters())
                    <div class="mt-4 flex items-center gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:button wire:click="resetFilters" variant="subtle" icon="x-mark" size="sm">
                            {{ __('common.actions.reset_filters') }}
                        </flux:button>

                        <span class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ trans_choice('documents.count', $documents->total(), ['count' => $documents->total()]) }}
                        </span>
                    </div>
                @endif
            </x-panel>
        </div>
    </div>

    @if ($documents->isEmpty())
        <x-empty-state
            icon="document-text"
            :title="$this->hasFilters() ? __('documents.empty.filtered_title') : __('documents.empty.title')"
            :description="$this->hasFilters() ? __('documents.empty.filtered_description') : __('documents.empty.description')"
        >
            @if ($this->hasFilters())
                <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="x-mark">
                    {{ __('common.actions.reset_filters') }}
                </flux:button>
            @elsecan('create', App\Models\Document::class)
                <flux:button :href="route('documents.create')" variant="primary" size="sm" icon="arrow-up-tray" wire:navigate>
                    {{ __('documents.create') }}
                </flux:button>
            @endcan
        </x-empty-state>
    @else
        {{-- Desktop table --}}
        <div class="max-lg:hidden" wire:loading.class="opacity-60">
            <x-panel :padded="false">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('documents.fields.document_number') }}</flux:table.column>
                        <flux:table.column>{{ __('documents.fields.title') }}</flux:table.column>
                        <flux:table.column>{{ __('documents.fields.project') }}</flux:table.column>
                        <flux:table.column>{{ __('documents.fields.discipline') }}</flux:table.column>
                        <flux:table.column align="center">{{ __('documents.fields.revision') }}</flux:table.column>
                        <flux:table.column>{{ __('documents.fields.status') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('common.labels.updated_at') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($documents as $document)
                            <flux:table.row :key="$document->id">
                                <flux:table.cell>
                                    <flux:link :href="route('documents.show', $document)" class="font-mono text-sm font-medium" wire:navigate>
                                        {{ $document->document_number }}
                                    </flux:link>
                                </flux:table.cell>

                                <flux:table.cell class="max-w-xs truncate">
                                    <span title="{{ $document->title }}">{{ $document->title }}</span>
                                </flux:table.cell>

                                <flux:table.cell class="text-zinc-500 dark:text-zinc-400">
                                    {{ $document->project?->project_code ?? '—' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($document->discipline)
                                        <flux:badge size="sm" color="zinc">{{ $document->discipline->code }}</flux:badge>
                                    @else
                                        —
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell align="center" class="font-mono">
                                    {{ $document->current_revision ?? '—' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <x-badge :status="$document->status" />
                                </flux:table.cell>

                                <flux:table.cell align="end" class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $document->updated_at->translatedFormat('d M Y') }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </x-panel>
        </div>

        {{-- Mobile cards (§42) --}}
        <div class="flex flex-col gap-3 lg:hidden" wire:loading.class="opacity-60">
            @foreach ($documents as $document)
                <a
                    href="{{ route('documents.show', $document) }}"
                    wire:navigate
                    wire:key="doc-card-{{ $document->id }}"
                    class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-white p-4 transition active:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:active:bg-zinc-800"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ $document->document_number }}</p>
                            <p class="mt-0.5 font-medium text-zinc-900 dark:text-white">{{ $document->title }}</p>
                        </div>
                        <x-badge :status="$document->status" />
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        @if ($document->discipline)
                            <flux:badge size="sm" color="zinc">{{ $document->discipline->code }}</flux:badge>
                        @endif

                        <span>{{ $document->project?->project_code }}</span>
                        <span aria-hidden="true">·</span>
                        <span class="font-mono">{{ __('documents.revision_label', ['revision' => $document->current_revision ?? '—']) }}</span>
                        <span aria-hidden="true">·</span>
                        <span>{{ $document->updated_at->translatedFormat('d M Y') }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        <flux:pagination :paginator="$documents" />
    @endif
</div>
