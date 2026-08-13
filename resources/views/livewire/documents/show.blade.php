<div class="flex flex-col gap-6">

    <flux:button :href="route('documents.index')" icon="arrow-left" variant="ghost" size="sm" class="self-start" wire:navigate>
        {{ __('documents.title') }}
    </flux:button>

    {{-- Header (§21) --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $document->document_number }}</span>
                <x-badge :status="$document->status" />
                <flux:badge size="sm" color="zinc" class="font-mono">
                    {{ __('documents.revision_label', ['revision' => $document->current_revision ?? '—']) }}
                </flux:badge>
            </div>

            <flux:heading size="xl" level="1" class="mt-1">{{ $document->title }}</flux:heading>

            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                @if ($document->project)
                    <flux:link :href="route('projects.show', $document->project)" variant="subtle" class="flex items-center gap-1.5" wire:navigate>
                        <flux:icon name="folder" variant="micro" aria-hidden="true" />
                        {{ $document->project->project_code }}
                    </flux:link>
                @endif

                @if ($document->discipline)
                    <span class="flex items-center gap-1.5">
                        <flux:icon name="squares-2x2" variant="micro" aria-hidden="true" />
                        {{ $document->discipline->name }}
                    </span>
                @endif

                @if ($document->creator)
                    <span class="flex items-center gap-1.5">
                        <flux:icon name="user" variant="micro" aria-hidden="true" />
                        {{ $document->creator->name }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Actions, each gated by its own policy method (§21) --}}
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @if ($currentVersion)
                @can('download', $document)
                    <flux:button
                        :href="route('documents.download', [$document, $currentVersion])"
                        icon="arrow-down-tray"
                        variant="outline"
                    >
                        {{ __('documents.actions.download') }}
                    </flux:button>
                @endcan
            @endif

            @can('uploadRevision', $document)
                @if ($document->status->acceptsNewRevision())
                    <flux:modal.trigger name="upload-revision">
                        <flux:button icon="arrow-up-tray" variant="outline">
                            {{ __('documents.actions.upload_revision') }}
                        </flux:button>
                    </flux:modal.trigger>
                @endif
            @endcan

            @can('assign', App\Models\Review::class)
                @if (in_array($document->status, [App\Enums\DocumentStatus::Draft, App\Enums\DocumentStatus::NeedsRevision, App\Enums\DocumentStatus::UnderReview], true))
                    <livewire:documents.assign-reviewers :document="$document" :key="'assign-'.$document->id.'-'.$document->current_revision" />
                @endif
            @endcan

            @can('submitForReview', $document)
                @if (in_array($document->status, [App\Enums\DocumentStatus::Draft, App\Enums\DocumentStatus::NeedsRevision], true))
                    <flux:button wire:click="submitForReview" icon="paper-airplane" variant="primary">
                        {{ __('documents.actions.submit_review') }}
                    </flux:button>
                @endif
            @endcan

            @canany(['update', 'archive'], $document)
                <flux:dropdown position="bottom" align="end">
                    <flux:button icon="ellipsis-horizontal" variant="ghost" :aria-label="__('common.actions.view')" />

                    <flux:menu>
                        @can('update', $document)
                            <flux:menu.item icon="pencil-square" :href="route('documents.edit', $document)" wire:navigate>
                                {{ __('common.actions.edit') }}
                            </flux:menu.item>
                        @endcan

                        @can('archive', $document)
                            <flux:menu.item
                                icon="{{ $document->status === App\Enums\DocumentStatus::Archived ? 'arrow-up-tray' : 'archive-box' }}"
                                wire:click="archive"
                            >
                                {{ $document->status === App\Enums\DocumentStatus::Archived
                                    ? __('documents.actions.unarchive')
                                    : __('documents.actions.archive') }}
                            </flux:menu.item>
                        @endcan
                    </flux:menu>
                </flux:dropdown>
            @endcanany
        </div>
    </div>

    {{-- Tabs --}}
    <div class="-mx-4 overflow-x-auto px-4 no-scrollbar sm:mx-0 sm:px-0">
        <nav class="flex min-w-max gap-1 border-b border-zinc-200 dark:border-zinc-700" aria-label="{{ __('documents.singular') }}">
            @foreach (['overview', 'revisions', 'reviews', 'approvals', 'comments', 'tasks', 'activity'] as $item)
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
                    {{ __('documents.tabs.'.$item) }}
                    @if ($item === 'revisions')
                        <span class="ms-1 text-xs text-zinc-400">{{ $versions->count() }}</span>
                    @endif
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Overview --}}
    @if ($tab === 'overview')
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="flex flex-col gap-6 xl:col-span-2">
                <x-panel :title="__('documents.fields.description')" icon="document-text">
                    @if ($document->description)
                        <p class="whitespace-pre-line text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $document->description }}</p>
                    @else
                        <p class="text-sm text-zinc-400">—</p>
                    @endif
                </x-panel>

                @if ($currentVersion)
                    <x-panel :title="__('documents.current_version')" icon="paper-clip">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-md bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                                    <flux:icon name="document" aria-hidden="true" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ $currentVersion->original_filename }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $currentVersion->formattedSize() }}
                                        · {{ __('documents.revision_label', ['revision' => $currentVersion->revision]) }}
                                        · {{ $currentVersion->created_at->translatedFormat('d M Y') }}
                                    </p>
                                </div>

                                @can('download', $document)
                                    <flux:button
                                        :href="route('documents.download', [$document, $currentVersion])"
                                        icon="arrow-down-tray"
                                        variant="ghost"
                                        size="sm"
                                        :tooltip="__('documents.actions.download')"
                                    />
                                @endcan
                            </div>

                            @if ($currentVersion->version_notes)
                                <div class="rounded-lg bg-zinc-50 p-3 text-sm text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    <p class="mb-1 text-xs font-medium uppercase tracking-wide text-zinc-500">
                                        {{ __('documents.fields.version_notes') }}
                                    </p>
                                    {{ $currentVersion->version_notes }}
                                </div>
                            @endif

                            @unless ($storage->exists($currentVersion))
                                <flux:callout variant="danger" icon="exclamation-triangle">
                                    <flux:callout.text>{{ __('documents.messages.file_missing') }}</flux:callout.text>
                                </flux:callout>
                            @endunless
                        </div>
                    </x-panel>
                @endif
            </div>

            <x-panel :title="__('documents.singular')" icon="information-circle">
                <dl class="flex flex-col gap-3 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('documents.fields.project') }}</dt>
                        <dd class="text-end">{{ $document->project?->project_code ?? '—' }}</dd>
                    </div>
                    <flux:separator variant="subtle" />
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('documents.fields.discipline') }}</dt>
                        <dd class="text-end">{{ $document->discipline?->name ?? '—' }}</dd>
                    </div>
                    <flux:separator variant="subtle" />
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('documents.fields.creator') }}</dt>
                        <dd class="text-end">{{ $document->creator?->name ?? '—' }}</dd>
                    </div>
                    <flux:separator variant="subtle" />
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('common.labels.created_at') }}</dt>
                        <dd class="text-end">{{ $document->created_at->translatedFormat('d M Y') }}</dd>
                    </div>
                    <flux:separator variant="subtle" />
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('common.labels.updated_at') }}</dt>
                        <dd class="text-end">{{ $document->updated_at->translatedFormat('d M Y') }}</dd>
                    </div>
                </dl>
            </x-panel>
        </div>

    {{-- Revision history (§22) --}}
    @elseif ($tab === 'revisions')
        <x-panel :padded="false">
            @if ($versions->isEmpty())
                <div class="p-4">
                    <x-empty-state icon="clock" :title="__('documents.no_versions')" compact />
                </div>
            @else
                <ol class="flex flex-col">
                    @foreach ($versions as $version)
                        <li
                            wire:key="rev-{{ $version->id }}"
                            @class([
                                'flex flex-col gap-3 p-4 sm:flex-row sm:items-center',
                                'border-b border-zinc-200 dark:border-zinc-700' => ! $loop->last,
                                'bg-brand-50/40 dark:bg-brand-500/5' => $version->revision === $document->current_revision,
                            ])
                        >
                            <div class="flex items-center gap-3 sm:w-40 sm:shrink-0">
                                <span class="grid size-9 shrink-0 place-items-center rounded-md bg-zinc-100 font-mono text-sm font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                    {{ $version->revision }}
                                </span>
                                <div class="sm:hidden">
                                    <p class="text-sm font-medium">{{ $version->original_filename }}</p>
                                </div>
                                @if ($version->revision === $document->current_revision)
                                    <flux:badge size="sm" color="sky">{{ __('documents.current_version') }}</flux:badge>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium max-sm:hidden">{{ $version->original_filename }}</p>

                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $version->uploader?->name ?? '—' }}
                                    · {{ $version->created_at->translatedFormat('d M Y à H:i') }}
                                    · {{ $version->formattedSize() }}
                                </p>

                                @if ($version->version_notes)
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $version->version_notes }}</p>
                                @endif
                            </div>

                            @can('download', $document)
                                <flux:button
                                    :href="route('documents.download', [$document, $version])"
                                    icon="arrow-down-tray"
                                    variant="ghost"
                                    size="sm"
                                    class="shrink-0 max-sm:self-start"
                                >
                                    <span class="sm:hidden">{{ __('documents.actions.download') }}</span>
                                    <span class="sr-only max-sm:hidden">
                                        {{ __('documents.actions.download_revision', ['revision' => $version->revision]) }}
                                    </span>
                                </flux:button>
                            @endcan
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-panel>

    {{-- Activity (§34) --}}
    @elseif ($tab === 'activity')
        <x-panel :padded="false">
            @if ($activities->isEmpty())
                <div class="p-4">
                    <x-empty-state icon="clock" :title="__('documents.empty.activity')" compact />
                </div>
            @else
                <ol class="flex flex-col">
                    @foreach ($activities as $activity)
                        <x-activity-item
                            :activity="$activity"
                            :last="$loop->last"
                            wire:key="act-{{ $activity->id }}"
                        />
                    @endforeach
                </ol>
            @endif
        </x-panel>

    {{-- Reviews on the current revision (§23) --}}
    @elseif ($tab === 'reviews')
        <x-panel :padded="false">
            @if ($reviews->isEmpty())
                <div class="p-4">
                    <x-empty-state
                        icon="eye"
                        :title="__('reviews.empty.none_on_document')"
                        :description="__('reviews.empty.none_on_document_hint')"
                        compact
                    />
                </div>
            @else
                <ol class="flex flex-col">
                    @foreach ($reviews as $review)
                        <li
                            wire:key="doc-review-{{ $review->id }}"
                            @class([
                                'flex flex-col gap-3 p-4 sm:flex-row sm:items-center',
                                'border-b border-zinc-200 dark:border-zinc-700' => ! $loop->last,
                            ])
                        >
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <x-user-avatar :user="$review->reviewer" size="sm" class="shrink-0" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">{{ $review->reviewer?->name }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('reviews.on_revision', ['revision' => $review->documentVersion?->revision]) }}
                                        @if ($review->deadline)
                                            · {{ $review->deadline->translatedFormat('d M Y') }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                <x-badge :status="$review->priority" />
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
                                >
                                    {{ __('reviews.actions.open') }}
                                </flux:button>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-panel>

    {{-- All review comments across this document's revisions (§25) --}}
    @elseif ($tab === 'comments')
        <x-panel>
            @if ($comments->isEmpty())
                <x-empty-state
                    icon="chat-bubble-left-right"
                    :title="__('reviews.comments.empty')"
                    :description="__('reviews.comments.empty_hint')"
                    compact
                />
            @else
                <div class="flex flex-col gap-5">
                    @foreach ($comments as $item)
                        <x-comment :comment="$item" wire:key="doc-comment-{{ $item->id }}" />
                    @endforeach
                </div>
            @endif
        </x-panel>

    {{-- Approval circuit on the current revision (§24) --}}
    @elseif ($tab === 'approvals')
        @php
            $myStep = $approvals->first(fn ($a) => $a->status === App\Enums\ApprovalStatus::InProgress
                && $a->approver_id === auth()->id());
        @endphp

        <x-panel :title="__('approvals.stepper.title')" icon="check-badge">
            @if ($myStep)
                <x-slot:actions>
                    <flux:modal.trigger name="approval-decision">
                        <flux:button size="sm" variant="primary" icon="check-circle">
                            {{ __('approvals.actions.approve') }}
                        </flux:button>
                    </flux:modal.trigger>
                </x-slot:actions>
            @endif

            <x-approval-stepper :approvals="$approvals" />
        </x-panel>

        @if ($myStep)
            <flux:modal name="approval-decision" class="max-w-md">
                <div class="flex flex-col gap-4">
                    <div>
                        <flux:heading size="lg">{{ __('common.confirm.title') }}</flux:heading>
                        <flux:subheading class="mt-1">
                            {{ __('approvals.confirm.approve') }}
                            @if ($myStep->step === $approvals->max('step'))
                                {{ __('approvals.confirm.approve_final') }}
                            @endif
                        </flux:subheading>
                    </div>

                    <flux:textarea
                        wire:model="approvalComment"
                        :label="__('approvals.fields.comment')"
                        :description="__('approvals.confirm.reject_hint')"
                        rows="3"
                    />
                    <flux:error name="approvalComment" />

                    <div class="flex flex-wrap justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('common.actions.cancel') }}</flux:button>
                        </flux:modal.close>

                        <flux:button
                            wire:click="rejectStep({{ $myStep->id }})"
                            variant="danger"
                            icon="x-circle"
                        >
                            {{ __('approvals.actions.reject') }}
                        </flux:button>

                        <flux:button
                            wire:click="approveStep({{ $myStep->id }})"
                            variant="primary"
                            icon="check-circle"
                        >
                            {{ __('approvals.actions.approve') }}
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        @endif

    {{-- Follow-up actions on this document (§27) --}}
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
                        :title="__('tasks.empty.none_on_document')"
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
                            wire:key="doc-task-{{ $task->id }}"
                        />
                    @endforeach
                </ul>
            @endif
        </x-panel>

        @can('create', App\Models\Task::class)
            <livewire:tasks.form
                :project-id="$document->project_id"
                :document-id="$document->id"
                :key="'task-form-doc-'.$document->id"
            />
        @endcan
    @endif

    {{-- New revision modal --}}
    @can('uploadRevision', $document)
        <flux:modal name="upload-revision" class="max-w-lg">
            <form wire:submit="uploadRevision" class="flex flex-col gap-5">
                <div>
                    <flux:heading size="lg">{{ __('documents.actions.upload_revision') }}</flux:heading>
                    <flux:subheading class="mt-1">
                        {{ __('documents.revision_label', ['revision' => $document->nextRevisionLabel()]) }}
                        — {{ __('documents.hints.version_notes') }}
                    </flux:subheading>
                </div>

                <flux:field>
                    <flux:label>{{ __('documents.fields.file') }}</flux:label>
                    <flux:input type="file" wire:model="revisionFile" />
                    <flux:error name="revisionFile" />
                </flux:field>

                <flux:textarea
                    wire:model="revisionNotes"
                    :label="__('documents.fields.version_notes')"
                    rows="3"
                />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">{{ __('common.actions.cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary" icon="arrow-up-tray" wire:loading.attr="disabled" wire:target="uploadRevision,revisionFile">
                        <span wire:loading.remove wire:target="uploadRevision">{{ __('common.actions.upload') }}</span>
                        <span wire:loading wire:target="uploadRevision">{{ __('common.states.saving') }}</span>
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan
</div>
