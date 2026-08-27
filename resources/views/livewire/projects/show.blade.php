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
                <flux:modal.trigger name="delete-project">
                    <flux:button icon="trash" variant="danger">{{ __('common.actions.delete') }}</flux:button>
                </flux:modal.trigger>
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

            <flux:pagination :paginator="$tasks" class="mt-4" />

            @can('create', App\Models\Task::class)
                <livewire:tasks.form :project-id="$project->id" :key="'task-form-proj-'.$project->id" />
            @endcan

        {{--
            Documents, revues, approbations et activité sont des vues en
            lecture seule, filtrées sur ce projet (§18). Chaque ligne renvoie
            vers le module correspondant, qui porte la recherche, les filtres
            et les actions — les dupliquer ici doublerait la surface à
            maintenir sans rien apporter.
        --}}
        @elseif ($tab === 'documents')
            {{-- Titled like the Tâches tab below: x-panel renders its header
                 as soon as an actions slot exists, so without a title the
                 upload button would sit against an empty bar. --}}
            <x-panel :title="__('documents.title')" icon="document-text" :padded="false">
                @can('create', App\Models\Document::class)
                    <x-slot:actions>
                        <flux:button
                            size="xs"
                            variant="ghost"
                            icon="arrow-up-tray"
                            :href="route('documents.create', ['project' => $project->id])"
                            wire:navigate
                        >
                            {{ __('documents.create') }}
                        </flux:button>
                    </x-slot:actions>
                @endcan

                @if ($documents->isEmpty())
                    <div class="p-4">
                        <x-empty-state
                            icon="document-text"
                            :title="__('projects.empty.documents')"
                            :description="__('projects.empty.documents_hint')"
                            compact
                        />
                    </div>
                @else
                    <ul class="flex flex-col">
                        @foreach ($documents as $document)
                            <li
                                wire:key="proj-doc-{{ $document->id }}"
                                @class([
                                    'flex flex-col gap-3 p-4 sm:flex-row sm:items-center',
                                    'border-b border-zinc-200 dark:border-zinc-700' => ! $loop->last,
                                ])
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <flux:link
                                            :href="route('documents.show', $document)"
                                            class="font-mono text-sm font-medium"
                                            wire:navigate
                                        >
                                            {{ $document->document_number }}
                                        </flux:link>

                                        @if ($document->discipline)
                                            <flux:badge size="sm" color="zinc">{{ $document->discipline->code }}</flux:badge>
                                        @endif
                                    </div>

                                    <p class="mt-0.5 truncate text-sm text-zinc-600 dark:text-zinc-300">
                                        {{ $document->title }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 flex-wrap items-center gap-2">
                                    <span class="font-mono text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('documents.revision_label', ['revision' => $document->current_revision ?? '—']) }}
                                    </span>

                                    <x-badge :status="$document->status" />

                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $document->updated_at->translatedFormat('d M Y') }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-panel>

            <flux:pagination :paginator="$documents" class="mt-4" />

        @elseif ($tab === 'reviews')
            <x-panel :padded="false">
                @if ($reviews->isEmpty())
                    <div class="p-4">
                        <x-empty-state
                            icon="eye"
                            :title="__('projects.empty.reviews')"
                            :description="__('projects.empty.reviews_hint')"
                            compact
                        />
                    </div>
                @else
                    <ul class="flex flex-col">
                        @foreach ($reviews as $review)
                            @php $document = $review->documentVersion?->document; @endphp

                            <li
                                wire:key="proj-review-{{ $review->id }}"
                                @class([
                                    'flex flex-col gap-3 p-4 sm:flex-row sm:items-center',
                                    'border-b border-zinc-200 dark:border-zinc-700' => ! $loop->last,
                                ])
                            >
                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    <x-user-avatar :user="$review->reviewer" size="sm" class="shrink-0" />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm">
                                            <span class="font-mono font-medium">{{ $document?->document_number }}</span>
                                            <span class="text-zinc-500 dark:text-zinc-400">
                                                · {{ $review->reviewer?->name }}
                                            </span>
                                        </p>
                                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ __('reviews.on_revision', ['revision' => $review->documentVersion?->revision]) }}
                                            @if ($review->deadline)
                                                · {{ $review->deadline->translatedFormat('d M Y') }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-wrap items-center gap-2">
                                    <x-badge :status="$review->status" />

                                    @if ($review->isOverdue())
                                        <flux:badge size="sm" color="red" icon="exclamation-triangle">
                                            {{ __('reviews.overdue') }}
                                        </flux:badge>
                                    @endif

                                    <flux:button
                                        :href="route('reviews.show', $review)"
                                        size="xs"
                                        variant="ghost"
                                        icon="arrow-right"
                                        wire:navigate
                                        :aria-label="__('reviews.actions.open')"
                                    />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-panel>

            <flux:pagination :paginator="$reviews" class="mt-4" />

        @elseif ($tab === 'approvals')
            <x-panel :padded="false">
                @if ($approvals->isEmpty())
                    <div class="p-4">
                        <x-empty-state
                            icon="check-badge"
                            :title="__('projects.empty.approvals')"
                            :description="__('projects.empty.approvals_hint')"
                            compact
                        />
                    </div>
                @else
                    <ul class="flex flex-col">
                        @foreach ($approvals as $approval)
                            @php $document = $approval->documentVersion?->document; @endphp

                            <li
                                wire:key="proj-appr-{{ $approval->id }}"
                                @class([
                                    'flex flex-col gap-3 p-4 sm:flex-row sm:items-center',
                                    'border-b border-zinc-200 dark:border-zinc-700' => ! $loop->last,
                                ])
                            >
                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    <x-user-avatar :user="$approval->approver" size="sm" class="shrink-0" />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm">
                                            <span class="font-mono font-medium">{{ $document?->document_number }}</span>
                                            <span class="text-zinc-500 dark:text-zinc-400">
                                                · {{ $approval->approver?->name ?? '—' }}
                                            </span>
                                        </p>
                                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ __('approvals.fields.step') }} {{ $approval->step }}
                                            · {{ __('documents.revision_label', ['revision' => $approval->documentVersion?->revision]) }}
                                            @if ($approval->deadline)
                                                · {{ $approval->deadline->translatedFormat('d M Y') }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-wrap items-center gap-2">
                                    <x-badge :status="$approval->status" />

                                    @if ($approval->isOverdue())
                                        <flux:badge size="sm" color="red" icon="exclamation-triangle">
                                            {{ __('approvals.overdue') }}
                                        </flux:badge>
                                    @endif

                                    <flux:button
                                        :href="route('documents.show', $document).'?tab=approvals'"
                                        size="xs"
                                        variant="ghost"
                                        icon="arrow-right"
                                        wire:navigate
                                        :aria-label="__('approvals.actions.open')"
                                    />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-panel>

            <flux:pagination :paginator="$approvals" class="mt-4" />

        @elseif ($tab === 'activity')
            <x-panel :padded="false">
                @if ($activities->isEmpty())
                    <div class="p-4">
                        <x-empty-state
                            icon="clock"
                            :title="__('projects.empty.activity')"
                            :description="__('projects.empty.activity_hint')"
                            compact
                        />
                    </div>
                @else
                    <ol class="flex flex-col">
                        @foreach ($activities as $activity)
                            <x-activity-item
                                :activity="$activity"
                                :last="$loop->last"
                                wire:key="proj-act-{{ $activity->id }}"
                            />
                        @endforeach
                    </ol>
                @endif
            </x-panel>

            <flux:pagination :paginator="$activities" class="mt-4" />
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

                    {{-- Name the collateral. The documents go with the project,
                         so the count is the part worth reading twice (§37). --}}
                    @php $atRisk = $project->documentsAtRisk(); @endphp

                    @if ($atRisk > 0)
                        <flux:callout variant="warning" icon="exclamation-triangle" class="mt-3">
                            <flux:callout.text>
                                {{ trans_choice('projects.messages.delete_cascade', $atRisk, ['count' => $atRisk]) }}
                            </flux:callout.text>
                        </flux:callout>
                    @endif
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
