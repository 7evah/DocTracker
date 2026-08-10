<?php

namespace App\Livewire\Reviews;

use App\Enums\Priority;
use App\Enums\ReviewStatus;
use App\Models\Project;
use App\Models\Review;
use App\Support\Permissions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The reviewer's queue (§23).
 *
 * Defaults to "assigned to me" because that is what a reviewer opens this
 * page to see; users who can assign reviews may widen the scope to all.
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
    public string $priority = '';

    #[Url(except: '')]
    public string $project = '';

    public int $perPage = 15;

    public function mount(): void
    {
        $this->authorize('viewAny', Review::class);

        if (! $this->canSeeAll()) {
            $this->scope = 'mine';
        }
    }

    public function canSeeAll(): bool
    {
        return auth()->user()->can(Permissions::REVIEWS_ASSIGN);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['scope', 'filter', 'priority', 'project'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('filter', 'priority', 'project');
        $this->resetPage();
    }

    public function hasFilters(): bool
    {
        return filled($this->filter) || filled($this->priority) || filled($this->project);
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        $base = fn () => Review::query()->when(
            $this->scope === 'mine' || ! $this->canSeeAll(),
            fn (Builder $q) => $q->where('reviewer_id', auth()->id()),
        );

        return [
            'pending' => $base()->open()->count(),
            'overdue' => $base()->overdue()->count(),
            'completed' => $base()->whereNotIn('status', ReviewStatus::openValues())->count(),
        ];
    }

    private function reviews(): LengthAwarePaginator
    {
        return Review::query()
            ->with([
                'reviewer:id,name,avatar_path',
                'documentVersion:id,document_id,revision',
                'documentVersion.document:id,project_id,discipline_id,document_number,title,status',
                'documentVersion.document.project:id,project_code',
                'documentVersion.document.discipline:id,code',
            ])
            ->when(
                $this->scope === 'mine' || ! $this->canSeeAll(),
                fn (Builder $q) => $q->where('reviewer_id', auth()->id()),
            )
            ->when($this->filter === 'pending', fn (Builder $q) => $q->open())
            ->when($this->filter === 'overdue', fn (Builder $q) => $q->overdue())
            ->when(
                $this->filter === 'completed',
                fn (Builder $q) => $q->whereNotIn('status', ReviewStatus::openValues()),
            )
            ->when($this->priority, fn (Builder $q) => $q->where('priority', $this->priority))
            ->when($this->project, fn (Builder $q) => $q->whereHas(
                'documentVersion.document',
                fn (Builder $d) => $d->where('project_id', $this->project),
            ))
            // Open reviews first, then by how soon they are due; undated last.
            ->orderByRaw('case when status in ("pending","in_progress") then 0 else 1 end')
            ->orderByRaw('deadline is null')
            ->orderBy('deadline')
            ->paginate($this->perPage)
            ->withQueryString();
    }

    public function render(): View
    {
        return view('livewire.reviews.index', [
            'reviews' => $this->reviews(),
            'counts' => $this->counts(),
            'priorities' => Priority::options(),
            'projects' => Project::query()->orderBy('project_code')->pluck('project_code', 'id'),
        ])->title(__('reviews.title'));
    }
}
