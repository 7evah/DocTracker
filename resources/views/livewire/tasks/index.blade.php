<div class="flex flex-col gap-6">

    <x-page-header :title="__('tasks.title')" :description="__('tasks.subtitle')">
        <x-slot:actions>
            @can('create', App\Models\Task::class)
                <flux:button icon="plus" variant="primary" wire:click="$dispatch('new-task')">
                    {{ __('tasks.create') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- Counters double as quick filters --}}
    <div class="grid grid-cols-3 gap-3">
        @foreach ([
            ['key' => 'open', 'icon' => 'inbox', 'tone' => 'info'],
            ['key' => 'overdue', 'icon' => 'exclamation-triangle', 'tone' => 'danger'],
            ['key' => 'completed', 'icon' => 'check-circle', 'tone' => 'success'],
        ] as $card)
            <button type="button" wire:click="$set('filter', '{{ $filter === $card['key'] ? '' : $card['key'] }}')" class="text-start">
                <x-stat-card
                    :label="__('tasks.filters.'.$card['key'])"
                    :value="$counts[$card['key']]"
                    :icon="$card['icon']"
                    :tone="$card['tone']"
                    @class(['ring-2 ring-brand-500' => $filter === $card['key']])
                />
            </button>
        @endforeach
    </div>

    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        <flux:input
            wire:model.live.debounce.300ms="search"
            :placeholder="__('tasks.filters.search')"
            icon="magnifying-glass"
            clearable
            class="lg:max-w-xs"
            :label="__('common.actions.search')"
            label:class="sr-only"
        />

        <div class="grid grid-cols-2 gap-3 lg:flex lg:items-center">
            <flux:select wire:model.live="scope" class="lg:w-52">
                <flux:select.option value="mine">{{ __('tasks.filters.mine') }}</flux:select.option>
                <flux:select.option value="created">{{ __('tasks.filters.created_by_me') }}</flux:select.option>
                @if ($this->canSeeAll())
                    <flux:select.option value="all">{{ __('tasks.filters.all') }}</flux:select.option>
                @endif
            </flux:select>

            <flux:select wire:model.live="priority" class="lg:w-44">
                <flux:select.option value="">{{ __('tasks.filters.priority') }}</flux:select.option>
                @foreach ($priorities as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="project" class="lg:w-48">
                <flux:select.option value="">{{ __('tasks.filters.project') }}</flux:select.option>
                @foreach ($projects as $id => $code)
                    <flux:select.option :value="$id">{{ $code }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($this->hasFilters())
                <flux:button wire:click="resetFilters" variant="subtle" icon="x-mark" size="sm" class="max-lg:col-span-2">
                    {{ __('common.actions.reset_filters') }}
                </flux:button>
            @endif
        </div>

        <flux:spacer class="max-lg:hidden" />

        <span class="text-sm text-zinc-500 max-lg:hidden dark:text-zinc-400">
            {{ trans_choice('tasks.count', $tasks->total(), ['count' => $tasks->total()]) }}
        </span>
    </div>

    @if ($tasks->isEmpty())
        <x-empty-state
            icon="clipboard-document-check"
            :title="$this->hasFilters() ? __('tasks.empty.filtered_title') : __('tasks.empty.title')"
            :description="$this->hasFilters() ? __('tasks.empty.filtered_description') : __('tasks.empty.description')"
        >
            @if ($this->hasFilters())
                <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="x-mark">
                    {{ __('common.actions.reset_filters') }}
                </flux:button>
            @elsecan('create', App\Models\Task::class)
                <flux:button wire:click="$dispatch('new-task')" variant="primary" size="sm" icon="plus">
                    {{ __('tasks.create') }}
                </flux:button>
            @endcan
        </x-empty-state>
    @else
        <x-panel :padded="false" wire:loading.class="opacity-60">
            <ul class="flex flex-col">
                @foreach ($tasks as $task)
                    <x-task-row :task="$task" :last="$loop->last" wire:key="task-{{ $task->id }}">
                        @can('complete', $task)
                            @if ($task->status === App\Enums\TaskStatus::Completed)
                                <flux:button
                                    wire:click="reopen({{ $task->id }})"
                                    size="xs"
                                    variant="ghost"
                                    icon="arrow-uturn-left"
                                >
                                    {{ __('tasks.actions.reopen') }}
                                </flux:button>
                            @else
                                <flux:button
                                    wire:click="complete({{ $task->id }})"
                                    size="xs"
                                    variant="ghost"
                                    icon="check"
                                >
                                    {{ __('tasks.actions.complete') }}
                                </flux:button>
                            @endif
                        @endcan

                        @can('update', $task)
                            <flux:button
                                wire:click="$dispatch('edit-task', { taskId: {{ $task->id }} })"
                                size="xs"
                                variant="ghost"
                                icon="pencil-square"
                                :aria-label="__('tasks.actions.edit')"
                            />
                        @endcan
                    </x-task-row>
                @endforeach
            </ul>
        </x-panel>

        <flux:pagination :paginator="$tasks" />
    @endif

    <livewire:tasks.form />
</div>
