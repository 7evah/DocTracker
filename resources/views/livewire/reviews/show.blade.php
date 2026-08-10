<div class="flex flex-col gap-6">

    <flux:button :href="route('reviews.index')" icon="arrow-left" variant="ghost" size="sm" class="self-start" wire:navigate>
        {{ __('reviews.title') }}
    </flux:button>

    {{-- Header --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $document?->document_number }}</span>
                <x-badge :status="$review->status" />
                <x-badge :status="$review->priority" />
                <flux:badge size="sm" color="zinc" class="font-mono">
                    {{ __('documents.revision_label', ['revision' => $version?->revision]) }}
                </flux:badge>

                @if ($review->isOverdue())
                    <flux:badge size="sm" color="red" icon="exclamation-triangle">{{ __('reviews.overdue') }}</flux:badge>
                @endif
            </div>

            <flux:heading size="xl" level="1" class="mt-1">{{ $document?->title }}</flux:heading>

            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                @if ($document?->project)
                    <flux:link :href="route('projects.show', $document->project)" variant="subtle" class="flex items-center gap-1.5" wire:navigate>
                        <flux:icon name="folder" variant="micro" aria-hidden="true" />
                        {{ $document->project->project_code }}
                    </flux:link>
                @endif

                <flux:link :href="route('documents.show', $document)" variant="subtle" class="flex items-center gap-1.5" wire:navigate>
                    <flux:icon name="document-text" variant="micro" aria-hidden="true" />
                    {{ __('documents.singular') }}
                </flux:link>

                @if ($review->deadline)
                    <span @class([
                        'flex items-center gap-1.5',
                        'font-medium text-red-600 dark:text-red-400' => $review->isOverdue(),
                    ])>
                        <flux:icon name="calendar-days" variant="micro" aria-hidden="true" />
                        {{ $review->deadline->translatedFormat('d M Y') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @if ($version)
                @can('download', $document)
                    <flux:button
                        :href="route('documents.download', [$document, $version])"
                        icon="arrow-down-tray"
                        variant="outline"
                    >
                        {{ __('documents.actions.download') }}
                    </flux:button>
                @endcan
            @endif

            @if ($this->canDecide())
                <flux:modal.trigger name="confirm-revision_requested">
                    <flux:button icon="arrow-path" variant="outline">{{ __('reviews.actions.request_revision') }}</flux:button>
                </flux:modal.trigger>

                <flux:modal.trigger name="confirm-rejected">
                    <flux:button icon="x-circle" variant="danger">{{ __('reviews.actions.reject') }}</flux:button>
                </flux:modal.trigger>

                <flux:modal.trigger name="confirm-approved">
                    <flux:button icon="check-circle" variant="primary">{{ __('reviews.actions.approve') }}</flux:button>
                </flux:modal.trigger>
            @endif
        </div>
    </div>

    @unless ($this->canDecide())
        @if ($review->status->isOpen())
            {{-- Explain read-only mode rather than just hiding the buttons (§37). --}}
            <flux:callout icon="information-circle" variant="secondary">
                <flux:callout.text>
                    {{ __('Cette revue est affectée à :name. Vous pouvez la consulter et commenter, mais pas rendre de décision.', ['name' => $review->reviewer?->name]) }}
                </flux:callout.text>
            </flux:callout>
        @endif
    @endunless

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        <div class="flex flex-col gap-6 xl:col-span-2">

            {{-- Preview (§33): PDF inline, everything else download-only --}}
            <x-panel :title="__('documents.actions.preview')" icon="eye" :padded="false">
                <x-slot:actions>
                    @if ($version)
                        @can('download', $document)
                            <flux:button
                                :href="route('documents.download', [$document, $version])"
                                icon="arrow-down-tray"
                                size="xs"
                                variant="ghost"
                            >
                                {{ __('common.actions.download') }}
                            </flux:button>
                        @endcan
                    @endif
                </x-slot:actions>

                @if ($version && $storage->exists($version) && in_array($version->mime_type, config('documents.previewable_mimes'), true))
                    <object
                        data="{{ route('documents.download', [$document, $version]) }}#toolbar=1"
                        type="{{ $version->mime_type }}"
                        class="h-[60vh] w-full"
                        aria-label="{{ $document?->title }}"
                    >
                        {{-- Rendered when the browser has no inline viewer. --}}
                        <div class="p-4">
                            <x-empty-state
                                icon="document"
                                :title="__('documents.preview.unavailable')"
                                :description="__('documents.preview.unavailable_hint')"
                                compact
                            />
                        </div>
                    </object>
                @else
                    <div class="p-4">
                        <x-empty-state
                            icon="document"
                            :title="__('documents.preview.unavailable')"
                            :description="__('documents.preview.unavailable_hint')"
                            compact
                        >
                            @if ($version)
                                @can('download', $document)
                                    <flux:button
                                        :href="route('documents.download', [$document, $version])"
                                        icon="arrow-down-tray"
                                        size="sm"
                                        variant="primary"
                                    >
                                        {{ __('common.actions.download') }}
                                    </flux:button>
                                @endcan
                            @endif
                        </x-empty-state>
                    </div>
                @endif
            </x-panel>

            {{-- Comments (§25) --}}
            <x-panel :title="__('reviews.comments.title')" icon="chat-bubble-left-right">
                <x-slot:actions>
                    @if ($openComments > 0)
                        <flux:badge size="sm" color="amber">
                            {{ trans_choice('reviews.comments.open_count', $openComments, ['count' => $openComments]) }}
                        </flux:badge>
                    @endif
                </x-slot:actions>

                <div class="flex flex-col gap-5">
                    @forelse ($comments as $item)
                        <div wire:key="comment-{{ $item->id }}" class="flex flex-col gap-3">
                            <x-comment :comment="$item">
                                @can('resolveComment', $review)
                                    @unless ($item->resolved)
                                        <flux:button
                                            wire:click="resolveComment({{ $item->id }})"
                                            wire:confirm="{{ __('reviews.comments.resolve') }} ?"
                                            size="xs"
                                            variant="ghost"
                                            icon="check"
                                        >
                                            {{ __('common.actions.resolve') }}
                                        </flux:button>
                                    @endunless
                                @endcan

                                <flux:button
                                    wire:click="$set('replyingTo', {{ $item->id }})"
                                    size="xs"
                                    variant="ghost"
                                    icon="arrow-uturn-left"
                                >
                                    {{ __('reviews.comments.reply') }}
                                </flux:button>
                            </x-comment>

                            @if ($item->replies->isNotEmpty())
                                <div class="ms-6 flex flex-col gap-3 border-s-2 border-zinc-100 ps-4 dark:border-zinc-700">
                                    @foreach ($item->replies as $replyItem)
                                        <x-comment :comment="$replyItem" compact wire:key="reply-{{ $replyItem->id }}" />
                                    @endforeach
                                </div>
                            @endif

                            @if ($replyingTo === $item->id)
                                <form wire:submit="reply" class="ms-6 flex flex-col gap-2">
                                    <flux:textarea
                                        wire:model="replyBody"
                                        :placeholder="__('reviews.comments.placeholder')"
                                        rows="2"
                                        :label="__('reviews.comments.reply')"
                                        label:class="sr-only"
                                    />
                                    <div class="flex gap-2">
                                        <flux:button type="submit" size="xs" variant="primary">
                                            {{ __('reviews.comments.submit') }}
                                        </flux:button>
                                        <flux:button type="button" wire:click="$set('replyingTo', null)" size="xs" variant="ghost">
                                            {{ __('common.actions.cancel') }}
                                        </flux:button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @empty
                        <x-empty-state
                            icon="chat-bubble-left-right"
                            :title="__('reviews.comments.empty')"
                            :description="__('reviews.comments.empty_hint')"
                            compact
                        />
                    @endforelse

                    @can('comment', $review)
                        <form wire:submit="addComment" class="flex flex-col gap-2 border-t border-zinc-200 pt-5 dark:border-zinc-700">
                            <flux:textarea
                                wire:model="comment"
                                :placeholder="__('reviews.comments.placeholder')"
                                rows="3"
                                :label="__('reviews.comments.title')"
                                label:class="sr-only"
                            />

                            <flux:button type="submit" variant="primary" icon="paper-airplane" class="self-start">
                                {{ __('reviews.comments.submit') }}
                            </flux:button>
                        </form>
                    @endcan
                </div>
            </x-panel>
        </div>

        {{-- Review metadata --}}
        <div class="flex flex-col gap-6">
            <x-panel :title="__('reviews.singular')" icon="eye">
                <dl class="flex flex-col gap-3 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('reviews.fields.reviewer') }}</dt>
                        <dd class="flex items-center gap-2">
                            <x-user-avatar :user="$review->reviewer" size="xs" />
                            <span>{{ $review->reviewer?->name }}</span>
                        </dd>
                    </div>
                    <flux:separator variant="subtle" />
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('reviews.fields.assigned_by') }}</dt>
                        <dd class="text-end">{{ $review->assigner?->name ?? '—' }}</dd>
                    </div>
                    <flux:separator variant="subtle" />
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('reviews.fields.assigned_at') }}</dt>
                        <dd class="text-end">{{ $review->assigned_at?->translatedFormat('d M Y') ?? '—' }}</dd>
                    </div>
                    <flux:separator variant="subtle" />
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('reviews.fields.deadline') }}</dt>
                        <dd @class(['text-end', 'font-medium text-red-600 dark:text-red-400' => $review->isOverdue()])>
                            {{ $review->deadline?->translatedFormat('d M Y') ?? __('reviews.no_deadline') }}
                        </dd>
                    </div>

                    @if ($review->reviewed_at)
                        <flux:separator variant="subtle" />
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('reviews.fields.reviewed_at') }}</dt>
                            <dd class="text-end">{{ $review->reviewed_at->translatedFormat('d M Y') }}</dd>
                        </div>
                    @endif
                </dl>

                @if (filled($review->summary) && ! $this->canDecide())
                    <div class="mt-4 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                        <p class="mb-1 text-xs font-medium uppercase tracking-wide text-zinc-500">
                            {{ __('reviews.fields.summary') }}
                        </p>
                        <p class="whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $review->summary }}</p>
                    </div>
                @endif
            </x-panel>

            @if ($this->canDecide())
                <x-panel :title="__('reviews.fields.summary')" icon="pencil-square">
                    <flux:textarea
                        wire:model="summary"
                        :placeholder="__('reviews.fields.summary')"
                        rows="6"
                        :label="__('reviews.fields.summary')"
                        label:class="sr-only"
                    />
                    <flux:error name="summary" />
                </x-panel>
            @endif
        </div>
    </div>

    {{-- Confirmation dialogs (§37) --}}
    @if ($this->canDecide())
        @foreach ([
            ['name' => 'approved', 'action' => 'approve', 'variant' => 'primary', 'icon' => 'check-circle'],
            ['name' => 'revision_requested', 'action' => 'requestRevision', 'variant' => 'primary', 'icon' => 'arrow-path'],
            ['name' => 'rejected', 'action' => 'reject', 'variant' => 'danger', 'icon' => 'x-circle'],
        ] as $dialog)
            <flux:modal name="confirm-{{ $dialog['name'] }}" class="max-w-md" wire:key="dlg-{{ $dialog['name'] }}">
                <div class="flex flex-col gap-4">
                    <div>
                        <flux:heading size="lg">{{ __('common.confirm.title') }}</flux:heading>
                        <flux:subheading class="mt-1">
                            {{ __('reviews.confirm.'.($dialog['name'] === 'approved' ? 'approve' : ($dialog['name'] === 'rejected' ? 'reject' : 'request_revision'))) }}
                        </flux:subheading>
                    </div>

                    @if ($dialog['name'] !== 'approved')
                        <flux:textarea
                            wire:model="summary"
                            :label="__('reviews.fields.summary')"
                            :description="__('reviews.confirm.summary_required')"
                            rows="4"
                        />
                    @endif

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('common.actions.cancel') }}</flux:button>
                        </flux:modal.close>

                        <flux:button
                            wire:click="{{ $dialog['action'] }}"
                            :variant="$dialog['variant']"
                            :icon="$dialog['icon']"
                        >
                            {{ __('common.actions.confirm') }}
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        @endforeach
    @endif
</div>
