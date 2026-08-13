<?php

namespace App\Livewire\Documents;

use App\Enums\DocumentStatus;
use App\Models\Approval;
use App\Models\Document;
use App\Models\ReviewComment;
use App\Services\ApprovalService;
use App\Services\DocumentService;
use App\Services\DocumentStorage;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Show extends Component
{
    use WithFileUploads;

    public Document $document;

    #[Url(except: 'overview')]
    public string $tab = 'overview';

    /** New-revision modal state. */
    public ?TemporaryUploadedFile $revisionFile = null;

    public string $revisionNotes = '';

    /** Shared by the approve and reject confirmation dialogs. */
    public string $approvalComment = '';

    public function mount(Document $document): void
    {
        $this->authorize('view', $document);

        $this->document = $document->load([
            'project:id,project_code,name',
            'discipline:id,code,name',
            'creator:id,name,avatar_path',
        ]);
    }

    /** Re-read the document after the assign-reviewers child changes its status. */
    #[On('reviews-assigned')]
    public function refreshDocument(): void
    {
        $this->document->refresh();
        $this->tab = 'reviews';
    }

    /*
    |--------------------------------------------------------------------------
    | Approval actions (§24)
    |--------------------------------------------------------------------------
    */

    public function approveStep(int $approvalId, ApprovalService $approvals): void
    {
        $approval = $this->approvalOnThisDocument($approvalId);

        $this->authorize('approve', $approval);

        $approvals->decide($approval, auth()->user(), approved: true, comment: $this->approvalComment ?: null);

        $this->finishApprovalAction();

        Flux::toast(
            text: $this->document->status === DocumentStatus::Approved
                ? __('approvals.messages.approved_final')
                : __('approvals.messages.approved'),
            variant: 'success',
        );
    }

    public function rejectStep(int $approvalId, ApprovalService $approvals): void
    {
        $approval = $this->approvalOnThisDocument($approvalId);

        $this->authorize('reject', $approval);

        // A rejection ends the circuit, so it must carry a reason (§37).
        $this->validate(
            ['approvalComment' => ['required', 'string', 'min:5', 'max:2000']],
            ['approvalComment.required' => __('approvals.confirm.comment_required')],
            ['approvalComment' => __('approvals.fields.comment')],
        );

        $approvals->decide($approval, auth()->user(), approved: false, comment: $this->approvalComment);

        $this->finishApprovalAction();

        Flux::toast(text: __('approvals.messages.rejected'), variant: 'warning');
    }

    /**
     * Resolve the step through this document's own relation, so an approval
     * id belonging to another document cannot be acted on from here (§39).
     */
    private function approvalOnThisDocument(int $approvalId): Approval
    {
        return $this->document->approvals()->findOrFail($approvalId);
    }

    private function finishApprovalAction(): void
    {
        $this->reset('approvalComment');
        $this->document->refresh();
        $this->modal('approval-decision')->close();
    }

    /*
    |--------------------------------------------------------------------------
    | Lifecycle actions
    |--------------------------------------------------------------------------
    */

    public function submitForReview(DocumentService $documents): void
    {
        $this->authorize('submitForReview', $this->document);

        // Status rule enforced server-side, not just by hiding the button (§39).
        if (! in_array($this->document->status, [DocumentStatus::Draft, DocumentStatus::NeedsRevision], true)) {
            Flux::toast(text: __('documents.messages.submit_blocked'), variant: 'danger');

            return;
        }

        $documents->submitForReview($this->document, auth()->user());

        $this->document->refresh();

        Flux::toast(text: __('documents.messages.submitted'), variant: 'success');
    }

    public function uploadRevision(DocumentService $documents): void
    {
        $this->authorize('uploadRevision', $this->document);

        if (! $this->document->status->acceptsNewRevision()) {
            Flux::toast(text: __('documents.messages.revision_blocked'), variant: 'danger');

            return;
        }

        $this->validate([
            'revisionFile' => [
                'required', 'file',
                'max:'.config('documents.max_size_kb'),
                'mimes:'.implode(',', config('documents.allowed_extensions')),
            ],
            'revisionNotes' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'revisionFile' => __('documents.fields.file'),
            'revisionNotes' => __('documents.fields.version_notes'),
        ]);

        $version = $documents->addRevision(
            document: $this->document,
            file: $this->revisionFile,
            author: auth()->user(),
            versionNotes: $this->revisionNotes ?: null,
        );

        $this->reset('revisionFile', 'revisionNotes');
        $this->document->refresh();
        $this->modal('upload-revision')->close();

        Flux::toast(
            text: __('documents.messages.revision_added', ['revision' => $version->revision]),
            variant: 'success',
        );
    }

    public function archive(DocumentService $documents): void
    {
        $this->authorize('archive', $this->document);

        $this->document->status === DocumentStatus::Archived
            ? $documents->unarchive($this->document, auth()->user())
            : $documents->archive($this->document, auth()->user());

        $this->document->refresh();

        Flux::toast(
            text: $this->document->status === DocumentStatus::Archived
                ? __('documents.messages.archived')
                : __('documents.messages.unarchived'),
            variant: 'success',
        );
    }

    public function render(): View
    {
        $versions = $this->document->versions()
            ->with('uploader:id,name,avatar_path')
            ->orderByDesc('id')
            ->get();

        $currentVersion = $versions->firstWhere('revision', $this->document->current_revision)
            ?? $versions->first();

        return view('livewire.documents.show', [
            'versions' => $versions,
            'currentVersion' => $currentVersion,
            'reviews' => $this->document->reviews()
                ->with(['reviewer:id,name,avatar_path', 'documentVersion:id,revision'])
                ->latest('reviews.id')
                ->get(),
            'comments' => ReviewComment::query()
                ->whereIn('review_id', $this->document->reviews()->select('reviews.id'))
                // 'author.roles' so <x-comment>'s role badge doesn't lazy-load
                // per row (§40) — see User::primaryRole().
                ->with(['author:id,name,avatar_path', 'author.roles', 'resolver:id,name'])
                ->latest()
                ->get(),
            // Approvals for the current revision only — earlier revisions
            // keep their own chain, which the history tab covers (§22).
            'approvals' => $currentVersion
                ? $currentVersion->approvals()->with('approver:id,name,avatar_path')->orderBy('step')->get()
                : collect(),
            'tasks' => $this->document->tasks()
                ->with(['assignee:id,name,avatar_path', 'project:id,project_code'])
                ->orderByRaw('case when status in ("open","in_progress") then 0 else 1 end')
                ->orderByRaw('due_date is null')
                ->orderBy('due_date')
                ->get(),
            'activities' => $this->document->activities()
                ->with('causer:id,name,avatar_path')
                ->latest()
                ->limit(30)
                ->get(),
            'storage' => app(DocumentStorage::class),
        ])->title($this->document->document_number);
    }
}
