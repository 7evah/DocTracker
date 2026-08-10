<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use App\Support\Permissions;

/**
 * Authorization for reviews (§13, §23).
 *
 * The recurring rule here is that holding the "review" permission is not
 * enough — a verdict may only be given by the person the review was actually
 * assigned to. Administrators bypass all of this via Gate::before.
 */
class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::REVIEWS_VIEW);
    }

    public function view(User $user, Review $review): bool
    {
        return $user->can(Permissions::REVIEWS_VIEW);
    }

    public function assign(User $user): bool
    {
        return $user->can(Permissions::REVIEWS_ASSIGN);
    }

    /**
     * Give a verdict. Restricted to the assigned reviewer: a second reviewer
     * holding the same permission must not be able to answer on their behalf,
     * because the review record is signed evidence of who checked what (§34).
     */
    public function decide(User $user, Review $review): bool
    {
        return $user->can(Permissions::DOCUMENTS_REVIEW)
            && $review->reviewer_id === $user->id
            && $review->status->isOpen();
    }

    /** Anyone who can see the review may join the discussion (§25). */
    public function comment(User $user, Review $review): bool
    {
        return $user->can(Permissions::REVIEWS_VIEW);
    }

    /**
     * Resolving a remark is the reviewer's call — or the document author's,
     * who is the one acting on it.
     */
    public function resolveComment(User $user, Review $review): bool
    {
        return $review->reviewer_id === $user->id
            || $review->documentVersion?->document?->created_by === $user->id
            || $user->can(Permissions::REVIEWS_ASSIGN);
    }
}
