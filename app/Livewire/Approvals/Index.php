<?php

namespace App\Livewire\Approvals;

use App\Enums\ApprovalStatus;
use App\Models\Approval;
use App\Models\Project;
use App\Support\Permissions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The approver's queue (§24).
 *
 * Mirrors the reviewer queue: defaults to "assigned to me", and only users
 * who can see the whole pipeline may widen the scope.
 */
class Index extends Component
{
    use WithPagination;

    #[Url(except: 'mine')]
    public string $scope = 'mine';

    /** pending | completed | overdue | '' */
    #[Url(except: '')]
    public string $filter = '';

    #[Url(except: '')]
    public string $project = '';

    public int $perPage = 15;

    public function mount(): void
    {
        $this->authorize('viewAny', Approval::class);

        if (! $this->canSeeAll()) {
            $this->scope = 'mine';
        }
    }

    public function canSeeAll(): bool
    {
        return auth()->user()->can(Permissions::REPORTS_VIEW)
            || auth()->user()->can(Permissions::REVIEWS_ASSIGN);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['scope', 'filter', 'project'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('filter', 'project');
        $this->resetPage();
    }

    public function hasFilters(): bool
    {
        return filled($this->filter) || filled($this->project);
    }

    private function scoped(): Builder
    {
        return Approval::query()->when(
            $this->scope === 'mine' || ! $this->canSeeAll(),
            fn (Builder $q) => $q->where('approver_id', auth()->id()),
        );
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        return [
            'pending' => $this->scoped()->open()->count(),
            'overdue' => $this->scoped()->overdue()->count(),
            'completed' => $this->scoped()->whereNotIn('status', ApprovalStatus::openValues())->count(),
        ];
    }

    private function approvals(): LengthAwarePaginator
    {
        return $this->scoped()
            ->with([
                'approver:id,name,avatar_path',
                'documentVersion:id,document_id,revision',
                'documentVersion.document:id,project_id,document_number,title,status',
                'documentVersion.document.project:id,project_code',
            ])
            ->when($this->filter === 'pending', fn (Builder $q) => $q->open())
            ->when($this->filter === 'overdue', fn (Builder $q) => $q->overdue())
            ->when(
                $this->filter === 'completed',
                fn (Builder $q) => $q->whereNotIn('status', ApprovalStatus::openValues()),
            )
            ->when($this->project, fn (Builder $q) => $q->whereHas(
                'documentVersion.document',
                fn (Builder $d) => $d->where('project_id', $this->project),
            ))
            // Actionable steps first, then by due date; undated last.
            ->orderByRaw('case when status = "in_progress" then 0 when status = "pending" then 1 else 2 end')
            ->orderByRaw('deadline is null')
            ->orderBy('deadline')
            ->paginate($this->perPage)
            ->withQueryString();
    }

    public function render(): View
    {
        return view('livewire.approvals.index', [
            'approvals' => $this->approvals(),
            'counts' => $this->counts(),
            'projects' => Project::query()->orderBy('project_code')->pluck('project_code', 'id'),
        ])->title(__('approvals.title'));
    }
}
