<?php

namespace App\Livewire\Projects;

use App\Enums\DocumentStatus;
use App\Enums\TaskStatus;
use App\Models\Approval;
use App\Models\Document;
use App\Models\Project;
use App\Models\Review;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class Show extends Component
{
    public Project $project;

    #[Url(except: 'overview')]
    public string $tab = 'overview';

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->project = $project->loadCount([
            'documents',
            'documents as approved_documents_count' => fn ($q) => $q->where('status', DocumentStatus::Approved),
            'documents as under_review_count' => fn ($q) => $q->where('status', DocumentStatus::UnderReview),
            'documents as needs_revision_count' => fn ($q) => $q->where('status', DocumentStatus::NeedsRevision),
            'tasks as open_tasks_count' => fn ($q) => $q->whereIn('status', TaskStatus::openValues()),
        ])->load('manager');
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->project);

        // Integrity rule, enforced server-side even though the UI hides the
        // button — a crafted request must not slip past it (§39).
        if (! $this->project->canBeDeleted()) {
            $this->modal('delete-project')->close();

            Flux::toast(
                text: __('projects.messages.delete_blocked'),
                variant: 'danger',
            );

            return;
        }

        $this->project->delete();

        session()->flash('toast', __('projects.messages.deleted'));
        $this->redirectRoute('projects.index', navigate: true);
    }

    /**
     * Document counts grouped by status, for the overview breakdown.
     *
     * @return array<string, int>
     */
    public function statusBreakdown(): array
    {
        return $this->project->documents()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Tab payloads
    |--------------------------------------------------------------------------
    |
    | Each tab loads only when it is the active one: this page is one Livewire
    | component with six panels, and eagerly running all six queries on every
    | render would cost five wasted round-trips per click (§40).
    |
    | These are deliberately read-only, scoped views. The full filterable
    | listings live in their own modules (§19, §23, §24) — duplicating the
    | search/filter chrome here would mean two places to fix every time those
    | rules change, so each row links out to the real page instead.
    */

    /** @return Collection<int, Document> */
    private function documents(): Collection
    {
        return $this->project->documents()
            ->with(['discipline:id,code,name', 'creator:id,name,avatar_path'])
            ->orderByDesc('updated_at')
            ->get();
    }

    /** @return Collection<int, Review> */
    private function reviews(): Collection
    {
        return Review::query()
            ->whereHas('documentVersion.document', $this->belongsToProject(...))
            ->with([
                'reviewer:id,name,avatar_path',
                'documentVersion:id,document_id,revision',
                'documentVersion.document:id,document_number,title',
            ])
            // Open reviews first, then soonest due; undated last. Matches the
            // ordering of the Reviews module so the two never disagree.
            ->orderByRaw('case when status in ("pending","in_progress") then 0 else 1 end')
            ->orderByRaw('deadline is null')
            ->orderBy('deadline')
            ->get();
    }

    /** @return Collection<int, Approval> */
    private function approvals(): Collection
    {
        return Approval::query()
            ->whereHas('documentVersion.document', $this->belongsToProject(...))
            ->with([
                'approver:id,name,avatar_path',
                'documentVersion:id,document_id,revision',
                'documentVersion.document:id,document_number,title',
            ])
            ->orderByRaw('case when status = "in_progress" then 0 else 1 end')
            ->orderByRaw('deadline is null')
            ->orderBy('deadline')
            ->orderBy('step')
            ->get();
    }

    private function belongsToProject(Builder $query): void
    {
        $query->where('project_id', $this->project->id);
    }

    /**
     * The project's own audit trail plus its documents' (§34).
     *
     * A project logs little on its own — creation and the odd status change —
     * so a feed of only that would be near-empty and useless. What someone
     * opens this tab for is what has been happening *inside* the project, so
     * the documents' entries are merged in.
     *
     * @return Collection<int, Activity>
     */
    private function activities(): Collection
    {
        $documentIds = $this->project->documents()->pluck('id');

        return Activity::query()
            ->with('causer:id,name,avatar_path')
            ->where(function (Builder $query) use ($documentIds) {
                $query
                    ->where(fn (Builder $q) => $q
                        ->where('subject_type', $this->project->getMorphClass())
                        ->where('subject_id', $this->project->id))
                    ->orWhere(fn (Builder $q) => $q
                        ->where('subject_type', (new Document)->getMorphClass())
                        ->whereIn('subject_id', $documentIds));
            })
            ->latest()
            ->limit(50)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.projects.show', [
            'documents' => $this->tab === 'documents' ? $this->documents() : collect(),
            'reviews' => $this->tab === 'reviews' ? $this->reviews() : collect(),
            'approvals' => $this->tab === 'approvals' ? $this->approvals() : collect(),
            'activities' => $this->tab === 'activity' ? $this->activities() : collect(),
            'tasks' => $this->tab === 'tasks'
                ? $this->project->tasks()
                    ->with(['assignee:id,name,avatar_path', 'document:id,document_number'])
                    ->orderByRaw('case when status in ("open","in_progress") then 0 else 1 end')
                    ->orderByRaw('due_date is null')
                    ->orderBy('due_date')
                    ->get()
                : collect(),
        ])->title($this->project->name);
    }
}
