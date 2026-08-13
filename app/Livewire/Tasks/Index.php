<?php

namespace App\Livewire\Tasks;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use App\Support\Permissions;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    /** mine | created | all */
    #[Url(except: 'mine')]
    public string $scope = 'mine';

    /** open | overdue | completed | '' */
    #[Url(except: '')]
    public string $filter = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $priority = '';

    #[Url(except: '')]
    public string $project = '';

    public int $perPage = 15;

    public function mount(): void
    {
        $this->authorize('viewAny', Task::class);
    }

    /**
     * Who may look beyond their own queue.
     *
     * Keyed off reports.view rather than tasks.update: the latter is held by
     * Engineers and Reviewers as well, so it does not describe oversight.
     * reports.view is the permission that actually separates the roles who
     * monitor other people's workload (PM, Approver, Admin) — the same test
     * the approvals queue uses.
     */
    public function canSeeAll(): bool
    {
        return auth()->user()->can(Permissions::REPORTS_VIEW);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['scope', 'filter', 'search', 'priority', 'project'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('filter', 'search', 'priority', 'project');
        $this->resetPage();
    }

    public function hasFilters(): bool
    {
        return filled($this->filter) || filled($this->search)
            || filled($this->priority) || filled($this->project);
    }

    /** Refresh when the shared task form saves. */
    #[On('task-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    public function complete(int $taskId, TaskService $tasks): void
    {
        $task = Task::findOrFail($taskId);

        $this->authorize('complete', $task);

        $tasks->complete($task, auth()->user());

        Flux::toast(text: __('tasks.messages.completed'), variant: 'success');
    }

    public function reopen(int $taskId, TaskService $tasks): void
    {
        $task = Task::findOrFail($taskId);

        $this->authorize('complete', $task);

        $tasks->reopen($task, auth()->user());

        Flux::toast(text: __('tasks.messages.reopened'), variant: 'success');
    }

    private function scoped(): Builder
    {
        return Task::query()
            ->when($this->scope === 'mine', fn (Builder $q) => $q->where('assigned_to', auth()->id()))
            ->when($this->scope === 'created', fn (Builder $q) => $q->where('created_by', auth()->id()))
            ->when(
                $this->scope === 'all' && ! $this->canSeeAll(),
                // Falling back rather than erroring keeps a tampered query
                // string harmless (§39).
                fn (Builder $q) => $q->where('assigned_to', auth()->id()),
            );
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        return [
            'open' => $this->scoped()->open()->count(),
            'overdue' => $this->scoped()->overdue()->count(),
            'completed' => $this->scoped()->where('status', TaskStatus::Completed)->count(),
        ];
    }

    private function tasks(): LengthAwarePaginator
    {
        return $this->scoped()
            ->with([
                'assignee:id,name,avatar_path',
                'project:id,project_code',
                'document:id,document_number',
            ])
            ->when($this->filter === 'open', fn (Builder $q) => $q->open())
            ->when($this->filter === 'overdue', fn (Builder $q) => $q->overdue())
            ->when($this->filter === 'completed', fn (Builder $q) => $q->where('status', TaskStatus::Completed))
            ->when($this->priority, fn (Builder $q) => $q->where('priority', $this->priority))
            ->when($this->project, fn (Builder $q) => $q->where('project_id', $this->project))
            ->when($this->search, function (Builder $q) {
                $term = '%'.str_replace(['%', '_'], ['\%', '\_'], trim($this->search)).'%';

                $q->where(fn (Builder $inner) => $inner
                    ->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term));
            })
            // Open work first, then soonest due; undated last.
            ->orderByRaw('case when status in ("open","in_progress") then 0 else 1 end')
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->paginate($this->perPage)
            ->withQueryString();
    }

    public function render(): View
    {
        return view('livewire.tasks.index', [
            'tasks' => $this->tasks(),
            'counts' => $this->counts(),
            'priorities' => Priority::options(),
            'projects' => Project::query()->orderBy('project_code')->pluck('project_code', 'id'),
        ])->title(__('tasks.title'));
    }
}
