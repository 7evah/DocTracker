<?php

namespace App\Livewire\Tasks;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Document;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Create/edit dialog for a task (§27).
 *
 * Reused by the tasks page, the document page and the project page, which is
 * why the project and document can be pre-set and locked by the host.
 */
class Form extends Component
{
    public ?Task $task = null;

    /** Pre-set by a host page; when present the field is locked. */
    public ?int $lockedProjectId = null;

    public ?int $lockedDocumentId = null;

    public string $title = '';

    public string $description = '';

    public string $project_id = '';

    public string $document_id = '';

    public string $assigned_to = '';

    public string $priority = Priority::Medium->value;

    public string $status = TaskStatus::Open->value;

    public string $due_date = '';

    public function mount(
        ?Task $task = null,
        ?int $projectId = null,
        ?int $documentId = null,
    ): void {
        $this->lockedProjectId = $projectId;
        $this->lockedDocumentId = $documentId;

        if ($task?->exists) {
            $this->fillFrom($task);

            return;
        }

        $this->project_id = (string) ($projectId ?? '');
        $this->document_id = (string) ($documentId ?? '');
    }

    #[On('edit-task')]
    public function edit(int $taskId): void
    {
        $task = Task::findOrFail($taskId);

        $this->authorize('update', $task);

        $this->fillFrom($task);
        $this->modal('task-form')->show();
    }

    #[On('new-task')]
    public function startNew(): void
    {
        $this->reset('task', 'title', 'description', 'assigned_to', 'due_date');
        $this->priority = Priority::Medium->value;
        $this->status = TaskStatus::Open->value;
        $this->project_id = (string) ($this->lockedProjectId ?? '');
        $this->document_id = (string) ($this->lockedDocumentId ?? '');

        $this->modal('task-form')->show();
    }

    private function fillFrom(Task $task): void
    {
        $this->task = $task;
        $this->title = $task->title;
        $this->description = $task->description ?? '';
        $this->project_id = (string) $task->project_id;
        $this->document_id = (string) ($task->document_id ?? '');
        $this->assigned_to = (string) ($task->assigned_to ?? '');
        $this->priority = $task->priority->value;
        $this->status = $task->status->value;
        $this->due_date = $task->due_date?->toDateString() ?? '';
    }

    public function isEditing(): bool
    {
        return $this->task !== null;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'project_id' => ['required', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'document_id' => [
                'nullable',
                // A task may only point at a document inside its own project,
                // otherwise the project page would list foreign documents.
                Rule::exists('documents', 'id')
                    ->where('project_id', $this->project_id ?: 0)
                    ->whereNull('deleted_at'),
            ],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')],
            'priority' => ['required', Rule::enum(Priority::class)],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'due_date' => ['nullable', 'date'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'title' => __('tasks.fields.title'),
            'description' => __('tasks.fields.description'),
            'project_id' => __('tasks.fields.project'),
            'document_id' => __('tasks.fields.document'),
            'assigned_to' => __('tasks.fields.assigned_to'),
            'priority' => __('tasks.fields.priority'),
            'status' => __('tasks.fields.status'),
            'due_date' => __('tasks.fields.due_date'),
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'document_id.exists' => __('tasks.messages.document_project_mismatch'),
        ];
    }

    /** Changing project invalidates a document chosen from the previous one. */
    public function updatedProjectId(): void
    {
        $this->document_id = '';
    }

    public function save(TaskService $tasks): void
    {
        $this->isEditing()
            ? $this->authorize('update', $this->task)
            : $this->authorize('create', Task::class);

        $validated = $this->validate();

        $validated['description'] = $validated['description'] ?: null;
        $validated['document_id'] = $validated['document_id'] ?: null;
        $validated['assigned_to'] = $validated['assigned_to'] ?: null;
        $validated['due_date'] = $validated['due_date'] ?: null;

        if ($this->isEditing()) {
            $tasks->update($this->task, $validated, auth()->user());
            $message = __('tasks.messages.updated');
        } else {
            $tasks->create($validated, auth()->user());
            $message = __('tasks.messages.created');
        }

        $this->modal('task-form')->close();
        $this->reset('task', 'title', 'description', 'assigned_to', 'due_date');

        $this->dispatch('task-saved');

        Flux::toast(text: $message, variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.tasks.form', [
            'projects' => Project::query()->orderBy('project_code')
                ->get(['id', 'project_code', 'name'])
                ->mapWithKeys(fn (Project $p) => [$p->id => "{$p->project_code} — {$p->name}"]),
            'documents' => $this->project_id
                ? Document::query()
                    ->where('project_id', $this->project_id)
                    ->orderBy('document_number')
                    ->pluck('document_number', 'id')
                : collect(),
            'assignees' => User::query()->active()->orderBy('name')->pluck('name', 'id'),
            'priorities' => Priority::options(),
            'statuses' => TaskStatus::options(),
        ]);
    }
}
