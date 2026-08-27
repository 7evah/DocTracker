<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Notifications\DocumentSubmittedForReview;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Document lifecycle (§7).
 *
 * Every state transition lives here rather than in a Livewire component, so
 * the same rules apply whether a change comes from the UI, a seeder, a queued
 * job or a future API (§5).
 */
class DocumentService
{
    public function __construct(
        private readonly DocumentStorage $storage,
        private readonly ReviewService $reviews,
    ) {}

    /**
     * Create a document together with its first revision.
     *
     * Wrapped in a transaction: a document row without its revision would be
     * a document with no file, which the rest of the app has no way to show.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(
        array $attributes,
        UploadedFile|TemporaryUploadedFile $file,
        User $author,
        ?string $versionNotes = null,
    ): Document {
        return DB::transaction(function () use ($attributes, $file, $author, $versionNotes) {
            $revision = $attributes['current_revision'] ?? 'A';

            $document = Document::create($attributes + [
                'created_by' => $author->id,
                'current_revision' => $revision,
                'status' => DocumentStatus::Draft,
            ]);

            $this->storeVersion($document, $file, $revision, $author, $versionNotes);

            activity('document')
                ->performedOn($document)
                ->causedBy($author)
                ->event('created')
                ->log('document.created');

            return $document->refresh();
        });
    }

    /**
     * Add a new revision. The previous revision is left untouched (§6).
     *
     * Uploading a revision moves the document back to Draft: the new file has
     * not been reviewed, so carrying over "Approved" would be a lie.
     */
    public function addRevision(
        Document $document,
        UploadedFile|TemporaryUploadedFile $file,
        User $author,
        ?string $versionNotes = null,
        ?string $revision = null,
    ): DocumentVersion {
        return DB::transaction(function () use ($document, $file, $author, $versionNotes, $revision) {
            $revision ??= $document->nextRevisionLabel();

            $version = $this->storeVersion($document, $file, $revision, $author, $versionNotes);

            $document->forceFill([
                'current_revision' => $revision,
                'status' => DocumentStatus::Draft,
            ])->save();

            activity('document')
                ->performedOn($document)
                ->causedBy($author)
                ->event('revision_uploaded')
                ->withProperties(['revision' => $revision])
                ->log('document.revision_uploaded');

            return $version;
        });
    }

    /**
     * Move a draft into review.
     *
     * The reviewer assignment itself belongs to the Reviews module; this only
     * performs the status transition and records it.
     */
    public function submitForReview(Document $document, User $actor): void
    {
        $document->forceFill(['status' => DocumentStatus::UnderReview])->save();

        activity('document')
            ->performedOn($document)
            ->causedBy($actor)
            ->event('submitted')
            ->withProperties(['revision' => $document->current_revision])
            ->log('document.submitted');

        $version = $document->latestVersion()->first();

        if (! $version) {
            return;
        }

        // Reviewers the manager marked as covering the whole document are
        // re-assigned here, so revision B onwards does not sit idle waiting
        // for the same choice to be made again.
        $carried = $this->reviews->carryForwardTo($version, $actor);

        /*
        | Tell the project manager either way: they are the one who assigns
        | reviewers, and before this a submitted revision was completely
        | silent — the author pressed the button and nobody heard about it.
        | Skipped when the manager is the submitter, matching how the rest of
        | the app declines to notify someone of their own action.
        */
        $manager = $document->project?->manager;

        if ($manager && $manager->isNot($actor)) {
            $manager->notify(new DocumentSubmittedForReview($version, $carried->isNotEmpty()));
        }
    }

    public function archive(Document $document, User $actor): void
    {
        $document->forceFill(['status' => DocumentStatus::Archived])->save();

        activity('document')
            ->performedOn($document)
            ->causedBy($actor)
            ->event('archived')
            ->log('document.archived');
    }

    /** Restore an archived document to draft so work can resume. */
    public function unarchive(Document $document, User $actor): void
    {
        $document->forceFill(['status' => DocumentStatus::Draft])->save();

        activity('document')
            ->performedOn($document)
            ->causedBy($actor)
            ->event('unarchived')
            ->log('document.unarchived');
    }

    private function storeVersion(
        Document $document,
        UploadedFile|TemporaryUploadedFile $file,
        string $revision,
        User $author,
        ?string $versionNotes,
    ): DocumentVersion {
        $stored = $this->storage->store($file, $document, $revision);

        return $document->versions()->create($stored + [
            'revision' => $revision,
            'version_notes' => $versionNotes ?: null,
            'uploaded_by' => $author->id,
        ]);
    }
}
