<?php

namespace App\Livewire\Projects;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    /*
    | Filter state lives in the query string so a filtered list is shareable
    | and survives a refresh — expected behaviour for an internal tool.
    */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $manager = '';

    #[Url(except: 'latest')]
    public string $sort = 'latest';

    #[Url(except: 12)]
    public int $perPage = 12;

    public function mount(): void
    {
        $this->authorize('viewAny', Project::class);
    }

    /** Any filter change must return to page one, or the user lands on an empty page. */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'manager', 'sort', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'manager', 'sort');
        $this->resetPage();
    }

    public function hasFilters(): bool
    {
        return filled($this->search) || filled($this->status) || filled($this->manager);
    }

    private function projects(): LengthAwarePaginator
    {
        return Project::query()
            // Eager-load the manager and aggregate counts up front (§40).
            ->with('manager')
            ->withListingCounts()
            ->search($this->search)
            ->when($this->status, fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->manager, fn (Builder $q) => $q->where('manager_id', $this->manager))
            ->tap(fn (Builder $q) => match ($this->sort) {
                'oldest' => $q->oldest(),
                'code' => $q->orderBy('project_code'),
                'name' => $q->orderBy('name'),
                'end_date' => $q->orderByRaw('end_date is null')->orderBy('end_date'),
                default => $q->latest(),
            })
            ->paginate($this->perPage)
            ->withQueryString();
    }

    public function render(): View
    {
        return view('livewire.projects.index', [
            'projects' => $this->projects(),
            'statuses' => ProjectStatus::options(),
            'managers' => User::query()
                ->whereHas('managedProjects')
                ->orderBy('name')
                ->pluck('name', 'id'),
        ])->title(__('projects.title'));
    }
}
