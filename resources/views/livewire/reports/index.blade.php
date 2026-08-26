<div class="flex flex-col gap-6">

    <x-page-header :title="__('reports.title')" :description="__('reports.subtitle')">
        <x-slot:actions>
            @if ($this->canExport())
                {{-- Plain links, not wire:navigate: these are file downloads. --}}
                <flux:button :href="$this->exportUrl('xlsx')" icon="table-cells" variant="outline">
                    <span class="max-sm:hidden">{{ __('reports.export.excel') }}</span>
                    <span class="sm:hidden">Excel</span>
                </flux:button>

                <flux:button :href="$this->exportUrl('pdf')" icon="document-arrow-down" variant="outline">
                    <span class="max-sm:hidden">{{ __('reports.export.pdf') }}</span>
                    <span class="sm:hidden">PDF</span>
                </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">

        {{-- Report picker: a list on desktop, a select on mobile (§42) --}}
        <div class="lg:col-span-1">
            <div class="lg:hidden">
                <flux:select wire:model.live="report" :label="__('reports.select_report')">
                    @foreach ($types as $option)
                        <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <x-panel :title="__('reports.select_report')" icon="chart-bar" :padded="false" class="max-lg:hidden">
                <nav class="flex flex-col p-2">
                    @foreach ($types as $option)
                        <button
                            type="button"
                            wire:click="$set('report', '{{ $option->value }}')"
                            @class([
                                'flex items-start gap-2.5 rounded-lg px-3 py-2.5 text-start transition',
                                'bg-brand-50 text-brand-800 dark:bg-brand-500/15 dark:text-brand-200' => $type === $option,
                                'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800' => $type !== $option,
                            ])
                            @if ($type === $option) aria-current="page" @endif
                        >
                            <flux:icon :name="$option->icon()" variant="micro" class="mt-0.5 shrink-0" aria-hidden="true" />
                            <span class="min-w-0">
                                <span class="block text-sm font-medium">{{ $option->label() }}</span>
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $option->description() }}</span>
                            </span>
                        </button>
                    @endforeach
                </nav>
            </x-panel>
        </div>

        <div class="flex flex-col gap-6 lg:col-span-3">

            {{-- Filters. Controls the report ignores are disabled with a
                 reason rather than hidden, so the form stays predictable. --}}
            <x-panel :title="__('reports.filters.title')" icon="funnel">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <flux:select
                        wire:model.live="project"
                        :label="__('common.labels.project')"
                        :disabled="! $type->usesProjectFilter()"
                        :description="$type->usesProjectFilter() ? null : __('reports.filters.not_applicable')"
                    >
                        <flux:select.option value="">{{ __('reports.filters.project') }}</flux:select.option>
                        @foreach ($projects as $id => $code)
                            <flux:select.option :value="$id">{{ $code }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select
                        wire:model.live="discipline"
                        :label="__('common.labels.discipline')"
                        :disabled="! $type->usesDisciplineFilter()"
                        :description="$type->usesDisciplineFilter() ? null : __('reports.filters.not_applicable')"
                    >
                        <flux:select.option value="">{{ __('reports.filters.discipline') }}</flux:select.option>
                        @foreach ($disciplines as $id => $label)
                            <flux:select.option :value="$id">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model.live="from" type="date" :label="__('reports.filters.from')" />
                    <flux:input wire:model.live="to" type="date" :label="__('reports.filters.to')" />
                </div>

                @if ($this->hasFilters())
                    <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:button wire:click="resetFilters" variant="subtle" icon="x-mark" size="sm">
                            {{ __('common.actions.reset_filters') }}
                        </flux:button>
                    </div>
                @endif
            </x-panel>

            {{-- Distribution chart. Plain CSS bars rather than a charting
                 library: one dependency-free bar per row, each labelled with
                 its own value so it is readable without the visual (§38, §58). --}}
            @if ($type->hasChart() && $result->chart !== [] && $result->chartMax() > 0)
                <x-panel :title="$result->title()" :icon="$type->icon()">
                    <dl class="flex flex-col gap-2.5">
                        @foreach ($result->chart as $label => $value)
                            <div class="flex items-center gap-3">
                                <dt class="w-28 shrink-0 truncate text-xs text-zinc-600 dark:text-zinc-300" title="{{ $label }}">
                                    {{ $label }}
                                </dt>
                                <dd class="flex flex-1 items-center gap-2">
                                    <div class="h-4 flex-1 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-800">
                                        <div
                                            class="h-full rounded bg-brand-600 dark:bg-brand-400"
                                            style="width: {{ $result->chartMax() > 0 ? round($value / $result->chartMax() * 100, 1) : 0 }}%"
                                        ></div>
                                    </div>
                                    <span class="w-10 shrink-0 text-end text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                        {{ $value }}
                                    </span>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </x-panel>
            @endif

            <x-panel :title="$result->title()" :icon="$type->icon()" :padded="false">
                <x-slot:actions>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ trans_choice('reports.rows', count($result->rows), ['count' => count($result->rows)]) }}
                    </span>
                </x-slot:actions>

                @if ($result->summary !== [])
                    <div class="flex flex-wrap gap-4 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        @foreach ($result->summary as $caption => $value)
                            <span class="text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $caption }} :
                                <strong class="text-zinc-900 dark:text-white">{{ $value }}</strong>
                            </span>
                        @endforeach
                    </div>
                @endif

                @if ($result->isEmpty())
                    <div class="p-4">
                        <x-empty-state
                            :icon="$type->icon()"
                            :title="__('reports.empty.title')"
                            :description="__('reports.empty.description')"
                            compact
                        />
                    </div>
                @else
                    {{--
                        Unlike the listings, this table's columns are decided by
                        the chosen report, so no per-column width can be given
                        here. w-full keeps it flush with the panel instead of
                        leaving a gap, table-fixed then shares the width evenly,
                        and every cell truncates so a long title ellipsises
                        rather than sliding under its neighbour. Reports are
                        inherently wide, so <ui-table-scroll-area> (see app.css)
                        still scrolls it in place rather than the page (§16).
                    --}}
                    <flux:table
                        class="w-full min-w-190
                            [&_td]:truncate
                            [&_th:first-child]:ps-4 [&_td:first-child]:ps-4
                            [&_th:last-child]:pe-4 [&_td:last-child]:pe-4"
                    >
                        <flux:table.columns>
                            @foreach ($result->headings as $heading)
                                <flux:table.column>{{ $heading }}</flux:table.column>
                            @endforeach
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($rows as $index => $row)
                                <flux:table.row :key="'row-'.$index">
                                    @foreach ($row as $cell)
                                        <flux:table.cell @class(['tabular-nums' => is_numeric($cell)])>
                                            {{ $cell }}
                                        </flux:table.cell>
                                    @endforeach
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>

                    <flux:pagination :paginator="$rows" class="border-t border-zinc-200 p-4 dark:border-zinc-700" />
                @endif
            </x-panel>
        </div>
    </div>
</div>
