<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\Priority;
use App\Enums\ReviewStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\User;
use App\Notifications\ReviewAssigned;
use App\Notifications\ReviewCompleted;
use App\Support\Permissions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Review workflow (§23).
 *
 * The important logic here is the rollup: a document's status is derived from
 * the verdicts on its current revision, never set by hand from a component.
 */
class ReviewService
{
    /**
     * Assign one or more reviewers to a revision and move the document into
     * review.
     *
     * @param  Collection<int, User>|array<int, User>  $reviewers
     * @return Collection<int, Review>
     */
    public function assign(
        DocumentVersion $version,
        iterable $reviewers,
        User $assigner,
        Priority $priority = Priority::Medium,
        ?Carbon $deadline = null,
    ): Collection {
        $deadline ??= now()->addDays($priority->defaultTurnaroundDays());

        return DB::transaction(function () use ($version, $reviewers, $assigner, $priority, $deadline) {
            $created = collect();

            foreach ($reviewers as $reviewer) {
                // updateOrCreate honours the unique(version, reviewer) index:
                // re-assigning someone refreshes their deadline rather than
                // failing or silently duplicating the row.
                $review = Review::updateOrCreate(
                    [
                        'document_version_id' => $version->id,
                        'reviewer_id' => $reviewer->id,
                    ],
                    [
                        'assigned_by' => $assigner->id,
                        'status' => ReviewStatus::Pending,
                        'priority' => $priority,
                        'assigned_at' => now(),
                        'deadline' => $deadline,
                    ],
                );

                $created->push($review);

                $reviewer->notify(new ReviewAssigned($review));
            }

            $document = $version->document;

            $document->forceFill(['status' => DocumentStatus::UnderReview])->save();

            activity('document')
                ->performedOn($document)
                ->causedBy($assigner)
                ->event('review_assigned')
                ->withProperties([
                    'revision' => $version->revision,
                    'reviewers' => $created->count(),
                ])
                ->log('document.review_assigned');

            return $created;
        });
    }

    /** Mark a review as actively in progress when the reviewer opens it. */
    public function start(Review $review, User $reviewer): void
    {
        if ($review->status !== ReviewStatus::Pending) {
            return;
        }

        $review->forceFill(['status' => ReviewStatus::InProgress])->save();
    }

    /**
     * Record a verdict and re-derive the document status.
     *
     * `summary` is required for anything other than an approval: a rejection
     * or revision request without a reason is unusable to the engineer.
     */
    public function decide(
        Review $review,
        User $reviewer,
        ReviewStatus $verdict,
        ?string $summary = null,
    ): void {
        DB::transaction(function () use ($review, $reviewer, $verdict, $summary) {
            $review->forceFill([
                'status' => $verdict,
                'summary' => $summary ?: $review->summary,
                'reviewed_at' => now(),
            ])->save();

            $document = $review->documentVersion->document;

            activity('document')
                ->performedOn($document)
                ->causedBy($reviewer)
                ->event('review_completed')
                ->withProperties([
                    'revision' => $review->documentVersion->revision,
                    'verdict' => $verdict->value,
                ])
                ->log('document.review_'.$verdict->value);

            $this->rollUpDocumentStatus($review->documentVersion->fresh());

            // Tell the author what happened; they are the one who must act.
            if ($author = $document->creator) {
                if ($author->isNot($reviewer)) {
                    $author->notify(new ReviewCompleted($review->fresh()));
                }
            }
        });
    }

    /**
     * Derive the document status from every review on the current revision.
     *
     * Order matters: a single rejection outranks everything, then any
     * revision request, and only a clean sweep of approvals promotes the
     * document. While any review is still open the document stays in review.
     */
    public function rollUpDocumentStatus(DocumentVersion $version): void
    {
        $document = $version->document;
        $reviews = $version->reviews()->get();

        if ($reviews->isEmpty()) {
            return;
        }

        $status = match (true) {
            $reviews->contains(fn (Review $r) => $r->status === ReviewStatus::Rejected) => DocumentStatus::Rejected,
            $reviews->contains(fn (Review $r) => $r->status === ReviewStatus::RevisionRequested) => DocumentStatus::NeedsRevision,
            $reviews->contains(fn (Review $r) => $r->status->isOpen()) => DocumentStatus::UnderReview,
            default => DocumentStatus::Approved,
        };

        if ($document->status !== $status) {
            $document->forceFill(['status' => $status])->save();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Comments (§25)
    |--------------------------------------------------------------------------
    */

    public function addComment(
        Review $review,
        User $author,
        string $body,
        ?int $parentId = null,
        ?int $page = null,
    ): ReviewComment {
        $comment = $review->comments()->create([
            'user_id' => $author->id,
            'parent_id' => $parentId,
            'comment' => $body,
            'page' => $page,
        ]);

        activity('document')
            ->performedOn($review->documentVersion->document)
            ->causedBy($author)
            ->event('commented')
            ->withProperties(['revision' => $review->documentVersion->revision])
            ->log('document.commented');

        return $comment;
    }

    public function resolveComment(ReviewComment $comment, User $user): void
    {
        $comment->markResolved($user);
    }

    /**
     * Candidate reviewers: active users holding the review permission,
     * excluding the document's own author — nobody reviews their own drawing.
     *
     * @return Collection<int, User>
     */
    public function candidates(Document $document): Collection
    {
        return User::query()
            ->active()
            ->permission(Permissions::DOCUMENTS_REVIEW)
            ->when($document->created_by, fn ($q, $id) => $q->whereKeyNot($id))
            ->orderBy('name')
            ->get();
    }
}
