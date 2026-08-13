<?php

namespace App\Livewire\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\ReviewComment;
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

        return view('livewire.documents.show', [
            'versions' => $versions,
            'currentVersion' => $versions->firstWhere('revision', $this->document->current_revision) ?? $versions->first(),
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
            'activities' => $this->document->activities()
                ->with('causer:id,name,avatar_path')
                ->latest()
                ->limit(30)
                ->get(),
            'storage' => app(DocumentStorage::class),
        ])->title($this->document->document_number);
    }
}
