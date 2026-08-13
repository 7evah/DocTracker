<?php

namespace App\Livewire\Projects;

use App\Enums\DocumentStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

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

    public function render(): View
    {
        return view('livewire.projects.show', [
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
