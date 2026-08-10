<?php

namespace App\Livewire\Documents;

use App\Enums\DocumentStatus;
use App\Models\Discipline;
use App\Models\Document;
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

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $project = '';

    #[Url(except: '')]
    public string $discipline = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $creator = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(except: 'latest')]
    public string $sort = 'latest';

    public int $perPage = 15;

    /** Whether the collapsible filter panel is open on small screens. */
    public bool $showFilters = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Document::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'project', 'discipline', 'status', 'creator', 'from', 'to', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'project', 'discipline', 'status', 'creator', 'from', 'to', 'sort');
        $this->resetPage();
    }

    public function hasFilters(): bool
    {
        return filled($this->search) || filled($this->project) || filled($this->discipline)
            || filled($this->status) || filled($this->creator) || filled($this->from) || filled($this->to);
    }

    private function documents(): LengthAwarePaginator
    {
        return Document::query()
            // Everything the row renders is eager-loaded (§40).
            ->with(['project:id,project_code,name', 'discipline:id,code,name', 'creator:id,name,avatar_path'])
            ->search($this->search)
            ->when($this->project, fn (Builder $q) => $q->where('project_id', $this->project))
            ->when($this->discipline, fn (Builder $q) => $q->where('discipline_id', $this->discipline))
            ->when($this->status, fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->creator, fn (Builder $q) => $q->where('created_by', $this->creator))
            ->when($this->from, fn (Builder $q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn (Builder $q) => $q->whereDate('created_at', '<=', $this->to))
            ->tap(fn (Builder $q) => match ($this->sort) {
                'oldest' => $q->oldest(),
                'number' => $q->orderBy('document_number'),
                'title' => $q->orderBy('title'),
                'status' => $q->orderBy('status'),
                default => $q->latest(),
            })
            ->paginate($this->perPage)
            ->withQueryString();
    }

    public function render(): View
    {
        return view('livewire.documents.index', [
            'documents' => $this->documents(),
            'projects' => Project::query()->orderBy('project_code')->pluck('project_code', 'id'),
            'disciplines' => Discipline::options(),
            'statuses' => DocumentStatus::options(),
            'creators' => User::query()->whereHas('createdDocuments')->orderBy('name')->pluck('name', 'id'),
        ])->title(__('documents.title'));
    }
}
