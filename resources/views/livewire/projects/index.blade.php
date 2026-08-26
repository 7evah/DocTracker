<div class="flex flex-col gap-6">

    <x-page-header :title="__('projects.title')" :description="__('projects.subtitle')">
        <x-slot:actions>
            @can('create', App\Models\Project::class)
                <flux:button icon="plus" variant="primary" :href="route('projects.create')" wire:navigate>
                    {{ __('projects.create') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- Filter bar: stacks on mobile, inline from `lg` (§16). --}}
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        <flux:input
            wire:model.live.debounce.300ms="search"
            :placeholder="__('projects.filters.search')"
            icon="magnifying-glass"
            clearable
            class="lg:max-w-xs"
            :label="__('common.actions.search')"
            label:class="sr-only"
        />

        <div class="grid grid-cols-2 gap-3 lg:flex lg:items-center">
            <flux:select wire:model.live="status" :placeholder="__('projects.filters.status')" class="lg:w-44">
                <flux:select.option value="">{{ __('projects.filters.status') }}</flux:select.option>
                @foreach ($statuses as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="manager" class="lg:w-52">
                <flux:select.option value="">{{ __('projects.filters.manager') }}</flux:select.option>
                @foreach ($managers as $id => $name)
                    <flux:select.option :value="$id">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="sort" class="lg:w-44">
                @foreach (['latest', 'oldest', 'code', 'name', 'end_date'] as $option)
                    <flux:select.option :value="$option">{{ __('projects.sort.'.$option) }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($this->hasFilters())
                <flux:button wire:click="resetFilters" variant="subtle" icon="x-mark" class="max-lg:col-span-2">
                    {{ __('common.actions.reset_filters') }}
                </flux:button>
            @endif
        </div>

        <flux:spacer class="max-lg:hidden" />

        <div
            class="text-sm text-zinc-500 max-lg:hidden dark:text-zinc-400"
            wire:loading.class="opacity-50"
        >
            {{ trans_choice('projects.documents_count', $projects->total(), ['count' => $projects->total()]) }}
        </div>
    </div>

    @if ($projects->isEmpty())
        <x-empty-state
            icon="folder"
            :title="$this->hasFilters() ? __('projects.empty.filtered_title') : __('projects.empty.title')"
            :description="$this->hasFilters() ? __('projects.empty.filtered_description') : __('projects.empty.description')"
        >
            @if ($this->hasFilters())
                <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="x-mark">
                    {{ __('common.actions.reset_filters') }}
                </flux:button>
            @elsecan('create', App\Models\Project::class)
                <flux:button :href="route('projects.create')" variant="primary" size="sm" icon="plus" wire:navigate>
                    {{ __('projects.create') }}
                </flux:button>
            @endcan
        </x-empty-state>
    @else
        {{--
            Table from `lg`, cards below (§42). Two markup paths rather than a
            horizontally-scrolling table, because a scrolling table is unusable
            on a phone and the spec forbids page-level horizontal scroll.
        --}}
        <div class="max-lg:hidden" wire:loading.class="opacity-60">
            <x-panel :padded="false">
                {{--
                    Flux's table is table-fixed, so the column widths come from
                    this header row alone. Percentages plus w-full spread the
                    slack thinly across every column instead of pooling it into
                    one conspicuous gap, and they are weighted by how long each
                    column's content actually runs. min-width forces genuine
                    overflow — and therefore a real scrollbar via Flux's own
                    <ui-table-scroll-area>, see app.css — once the viewport is
                    too narrow for even these shares (§16, §42).
                --}}
                <flux:table
                    class="w-full min-w-230
                        [&_th:first-child]:ps-4 [&_td:first-child]:ps-4
                        [&_th:last-child]:pe-4 [&_td:last-child]:pe-4"
                >
                    <flux:table.columns>
                        <flux:table.column class="w-[14%]">{{ __('projects.fields.project_code') }}</flux:table.column>
                        <flux:table.column class="w-[18%]">{{ __('projects.fields.name') }}</flux:table.column>
                        <flux:table.column class="w-[13%]">{{ __('projects.fields.client') }}</flux:table.column>
                        <flux:table.column class="w-[17%]">{{ __('projects.fields.manager') }}</flux:table.column>
                        <flux:table.column class="w-[14%]">{{ __('projects.stats.progress') }}</flux:table.column>
                        <flux:table.column class="w-[11%]">{{ __('projects.fields.status') }}</flux:table.column>
                        <flux:table.column align="end" class="w-[13%]">{{ __('projects.fields.end_date') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($projects as $project)
                            <flux:table.row :key="$project->id">
                                <flux:table.cell class="truncate">
                                    <flux:link :href="route('projects.show', $project)" class="font-medium" wire:navigate>
                                        {{ $project->project_code }}
                                    </flux:link>
                                </flux:table.cell>

                                <flux:table.cell class="truncate">
                                    <span title="{{ $project->name }}">{{ $project->name }}</span>
                                </flux:table.cell>

                                <flux:table.cell class="truncate text-zinc-500 dark:text-zinc-400">
                                    {{ $project->client ?: '—' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($project->manager)
                                        <div class="flex items-center gap-2">
                                            <x-user-avatar :user="$project->manager" size="xs" />
                                            <span class="truncate">{{ $project->manager->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-zinc-400">{{ __('projects.no_manager') }}</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <x-progress-bar
                                        :value="$project->documentProgress()"
                                        :label="__('projects.stats.progress')"
                                        :caption="$project->approved_documents_count.'/'.$project->documents_count"
                                    />
                                </flux:table.cell>

                                <flux:table.cell>
                                    <x-badge :status="$project->status" />
                                </flux:table.cell>

                                <flux:table.cell align="end">
                                    @if ($project->end_date)
                                        <span @class([
                                            'text-sm',
                                            'text-red-600 dark:text-red-400 font-medium' => $project->isOverdue(),
                                            'text-zinc-500 dark:text-zinc-400' => ! $project->isOverdue(),
                                        ])>
                                            @if ($project->isOverdue())
                                                <flux:icon name="exclamation-triangle" variant="micro" class="inline align-text-bottom" />
                                                <span class="sr-only">{{ __('projects.overdue') }} —</span>
                                            @endif
                                            {{ $project->end_date->translatedFormat('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </x-panel>
        </div>

        {{-- Mobile card list --}}
        <div class="flex flex-col gap-3 lg:hidden" wire:loading.class="opacity-60">
            @foreach ($projects as $project)
                <a
                    href="{{ route('projects.show', $project) }}"
                    wire:navigate
                    wire:key="card-{{ $project->id }}"
                    class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-white p-4 transition active:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:active:bg-zinc-800"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ $project->project_code }}</p>
                            <p class="mt-0.5 truncate font-medium text-zinc-900 dark:text-white">{{ $project->name }}</p>
                        </div>
                        <x-badge :status="$project->status" />
                    </div>

                    @if ($project->client)
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $project->client }}</p>
                    @endif

                    <x-progress-bar
                        :value="$project->documentProgress()"
                        :label="__('projects.stats.progress')"
                        :caption="$project->approved_documents_count.'/'.$project->documents_count"
                    />

                    <div class="flex items-center justify-between gap-3 text-sm">
                        <div class="flex min-w-0 items-center gap-2">
                            @if ($project->manager)
                                <x-user-avatar :user="$project->manager" size="xs" />
                                <span class="truncate text-zinc-600 dark:text-zinc-300">{{ $project->manager->name }}</span>
                            @else
                                <span class="text-zinc-400">{{ __('projects.no_manager') }}</span>
                            @endif
                        </div>

                        @if ($project->end_date)
                            <span @class([
                                'shrink-0 text-xs',
                                'text-red-600 dark:text-red-400 font-medium' => $project->isOverdue(),
                                'text-zinc-500 dark:text-zinc-400' => ! $project->isOverdue(),
                            ])>
                                {{ $project->end_date->translatedFormat('d M Y') }}
                            </span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <flux:pagination :paginator="$projects" />
    @endif
</div>
