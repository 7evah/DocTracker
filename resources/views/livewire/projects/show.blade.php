<div class="flex flex-col gap-6">

    <flux:button :href="route('projects.index')" icon="arrow-left" variant="ghost" size="sm" class="self-start" wire:navigate>
        {{ __('projects.title') }}
    </flux:button>

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $project->project_code }}</span>
                <x-badge :status="$project->status" />

                @if ($project->isOverdue())
                    <flux:badge color="red" size="sm" icon="exclamation-triangle">
                        {{ __('projects.overdue') }}
                    </flux:badge>
                @endif
            </div>

            <flux:heading size="xl" level="1" class="mt-1">{{ $project->name }}</flux:heading>

            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                @if ($project->client)
                    <span class="flex items-center gap-1.5">
                        <flux:icon name="building-office-2" variant="micro" aria-hidden="true" />
                        {{ $project->client }}
                    </span>
                @endif

                @if ($project->location)
                    <span class="flex items-center gap-1.5">
                        <flux:icon name="map-pin" variant="micro" aria-hidden="true" />
                        {{ $project->location }}
                    </span>
                @endif

                @if ($project->start_date || $project->end_date)
                    <span class="flex items-center gap-1.5">
                        <flux:icon name="calendar-days" variant="micro" aria-hidden="true" />
                        {{ $project->start_date?->translatedFormat('d M Y') ?? '—' }}
                        →
                        {{ $project->end_date?->translatedFormat('d M Y') ?? '—' }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @can('update', $project)
                <flux:button :href="route('projects.edit', $project)" icon="pencil-square" variant="ghost" wire:navigate>
                    {{ __('common.actions.edit') }}
                </flux:button>
            @endcan

            @can('delete', $project)
                @if ($project->canBeDeleted())
                    <flux:modal.trigger name="delete-project">
                        <flux:button icon="trash" variant="danger">{{ __('common.actions.delete') }}</flux:button>
                    </flux:modal.trigger>
                @else
                    {{-- Explain why rather than silently hiding the action (§37). --}}
                    <flux:button icon="trash" variant="danger" disabled :tooltip="__('projects.messages.delete_blocked')">
                        {{ __('common.actions.delete') }}
                    </flux:button>
                @endif
            @endcan
        </div>
    </div>

    {{-- Key figures --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat-card :label="__('projects.stats.documents')" :value="$project->documents_count" icon="document-text" tone="brand" />
        <x-stat-card :label="__('projects.stats.approved')" :value="$project->approved_documents_count" icon="check-circle" tone="success" />
        <x-stat-card :label="__('dashboard.stats.needs_revision')" :value="$project->needs_revision_count" icon="arrow-path" tone="warning" />
        <x-stat-card :label="__('projects.stats.open_tasks')" :value="$project->open_tasks_count" icon="clipboard-document-check" tone="info" />
    </div>

    {{--
        Tabs are plain links driven by a query-string property, so each tab is
        deep-linkable and works without JS. They scroll horizontally on narrow
        screens rather than wrapping into an unreadable stack (§42).
    --}}
    <div class="-mx-4 overflow-x-auto px-4 no-scrollbar sm:mx-0 sm:px-0">
        <nav class="flex min-w-max gap-1 border-b border-zinc-200 dark:border-zinc-700" aria-label="{{ __('projects.singular') }}">
            @foreach (['overview', 'documents', 'reviews', 'approvals', 'tasks', 'activity'] as $item)
                <button
                    type="button"
                    wire:click="$set('tab', '{{ $item }}')"
                    @class([
                        'whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition',
                        'border-brand-700 text-brand-700 dark:border-brand-400 dark:text-brand-400' => $tab === $item,
                        'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white' => $tab !== $item,
                    ])
                    @if ($tab === $item) aria-current="page" @endif
                >
                    {{ __('projects.tabs.'.$item) }}
                </button>
            @endforeach
        </nav>
    </div>

    <div>
        @if ($tab === 'overview')
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <x-panel :title="__('projects.fields.description')" icon="document-text" class="xl:col-span-2">
                    @if ($project->description)
                        <p class="whitespace-pre-line text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
                            {{ $project->description }}
                        </p>
                    @else
                        <p class="text-sm text-zinc-400">—</p>
                    @endif
                </x-panel>

                <x-panel :title="__('projects.singular')" icon="information-circle">
                    <dl class="flex flex-col gap-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('projects.fields.manager') }}</dt>
                            <dd class="text-end">
                                @if ($project->manager)
                                    <div class="flex items-center gap-2">
                                        <x-user-avatar :user="$project->manager" size="xs" />
                                        <span>{{ $project->manager->name }}</span>
                                    </div>
                                @else
                                    <span class="text-zinc-400">{{ __('projects.no_manager') }}</span>
                                @endif
                            </dd>
                        </div>

                        <flux:separator variant="subtle" />

                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('projects.fields.client') }}</dt>
                            <dd class="text-end">{{ $project->client ?: '—' }}</dd>
                        </div>

                        <flux:separator variant="subtle" />

                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('projects.fields.location') }}</dt>
                            <dd class="text-end">{{ $project->location ?: '—' }}</dd>
                        </div>

                        <flux:separator variant="subtle" />

                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('projects.stats.progress') }}</dt>
                            <dd><x-progress-bar :value="$project->documentProgress()" :label="__('projects.stats.progress')" /></dd>
                        </div>

                        <flux:separator variant="subtle" />

                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('common.labels.created_at') }}</dt>
                            <dd class="text-end">{{ $project->created_at->translatedFormat('d M Y') }}</dd>
                        </div>
                    </dl>
                </x-panel>
            </div>
        @elseif ($tab === 'tasks')
            <x-panel :title="__('tasks.title')" icon="clipboard-document-check" :padded="false">
                @can('create', App\Models\Task::class)
                    <x-slot:actions>
                        <flux:button size="xs" variant="ghost" icon="plus" wire:click="$dispatch('new-task')">
                            {{ __('tasks.create') }}
                        </flux:button>
                    </x-slot:actions>
                @endcan

                @if ($tasks->isEmpty())
                    <div class="p-4">
                        <x-empty-state
                            icon="clipboard-document-check"
                            :title="__('tasks.empty.none_on_project')"
                            compact
                        />
                    </div>
                @else
                    <ul class="flex flex-col">
                        @foreach ($tasks as $task)
                            <x-task-row
                                :task="$task"
                                :show-project="false"
                                :last="$loop->last"
                                wire:key="proj-task-{{ $task->id }}"
                            />
                        @endforeach
                    </ul>
                @endif
            </x-panel>

            @can('create', App\Models\Task::class)
                <livewire:tasks.form :project-id="$project->id" :key="'task-form-proj-'.$project->id" />
            @endcan

        @else
            {{-- Remaining tabs light up as their modules land (§48). --}}
            <x-empty-state
                icon="{{ ['documents' => 'document-text', 'reviews' => 'eye', 'approvals' => 'check-badge', 'activity' => 'clock'][$tab] ?? 'inbox' }}"
                :title="__('dashboard.coming_soon')"
                :description="__('Ce contenu sera disponible avec le module correspondant.')"
            />
        @endif
    </div>

    @can('delete', $project)
        <flux:modal name="delete-project" class="max-w-md">
            <div class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg">{{ __('common.confirm.title') }}</flux:heading>
                    <flux:subheading class="mt-1">
                        {{ __('projects.messages.delete_confirm') }}
                        {{ __('common.confirm.irreversible') }}
                    </flux:subheading>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('common.actions.cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button wire:click="delete" variant="danger" icon="trash">
                        {{ __('common.actions.delete') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endcan
</div>
