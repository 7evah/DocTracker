@props([
    'task' => null,
    'showProject' => true,
    'last' => false,
])

{{--
    One task line (§27, §43). Shared by the tasks page, the document page and
    the project page so a task looks identical wherever it surfaces.

    Overdue is signalled with an icon and a screen-reader label as well as
    colour (§38).
--}}

<li
    {{ $attributes->class([
        'flex flex-col gap-3 p-4 sm:flex-row sm:items-center',
        'border-b border-zinc-200 dark:border-zinc-700' => ! $last,
    ]) }}
>
    <div class="flex min-w-0 flex-1 items-start gap-3">
        <flux:icon
            :name="$task->status->icon()"
            variant="micro"
            @class([
                'mt-0.5 shrink-0',
                'text-green-600 dark:text-green-400' => $task->status === App\Enums\TaskStatus::Completed,
                'text-zinc-400' => $task->status !== App\Enums\TaskStatus::Completed,
            ])
            aria-hidden="true"
        />

        <div class="min-w-0">
            <p @class([
                'text-sm font-medium',
                'text-zinc-500 line-through dark:text-zinc-400' => $task->status === App\Enums\TaskStatus::Completed,
                'text-zinc-900 dark:text-white' => $task->status !== App\Enums\TaskStatus::Completed,
            ])>
                {{ $task->title }}
            </p>

            <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                @if ($task->assignee)
                    <span class="flex items-center gap-1">
                        <x-user-avatar :user="$task->assignee" size="xs" />
                        {{ $task->assignee->name }}
                    </span>
                @else
                    <span>{{ __('tasks.unassigned') }}</span>
                @endif

                @if ($showProject && $task->project)
                    <span aria-hidden="true">·</span>
                    <span>{{ $task->project->project_code }}</span>
                @endif

                @if ($task->document)
                    <span aria-hidden="true">·</span>
                    <span class="font-mono">{{ $task->document->document_number }}</span>
                @endif

                @if ($task->due_date)
                    <span aria-hidden="true">·</span>
                    <span @class(['font-medium text-red-600 dark:text-red-400' => $task->isOverdue()])>
                        @if ($task->isOverdue())
                            <flux:icon name="exclamation-triangle" variant="micro" class="inline align-text-bottom" />
                            <span class="sr-only">{{ __('tasks.overdue') }} —</span>
                        @endif
                        {{ $task->due_date->translatedFormat('d M Y') }}
                    </span>
                @endif
            </p>
        </div>
    </div>

    <div class="flex shrink-0 flex-wrap items-center gap-2">
        <x-badge :status="$task->priority" />
        <x-badge :status="$task->status" />

        {{ $slot }}
    </div>
</li>
