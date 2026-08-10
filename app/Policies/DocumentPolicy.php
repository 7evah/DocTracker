<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Support\Permissions;

/**
 * Authorization for documents (§13).
 *
 * Administrators short-circuit every method via the Gate::before hook.
 * Rules that must hold regardless of role are integrity rules and live on the
 * model instead — see Document::acceptsNewRevision().
 */
class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::DOCUMENTS_VIEW);
    }

    public function view(User $user, Document $document): bool
    {
        return $user->can(Permissions::DOCUMENTS_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::DOCUMENTS_CREATE);
    }

    /**
     * Metadata edits. An engineer may correct their own document; changing
     * someone else's requires the project-manager level permission.
     */
    public function update(User $user, Document $document): bool
    {
        if (! $user->can(Permissions::DOCUMENTS_UPDATE)) {
            return false;
        }

        return $document->created_by === $user->id
            || $user->can(Permissions::DOCUMENTS_DELETE);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->can(Permissions::DOCUMENTS_DELETE);
    }

    /** Downloading is separately permissioned so Viewers can read but not edit (§32). */
    public function download(User $user, Document $document): bool
    {
        return $user->can(Permissions::DOCUMENTS_DOWNLOAD);
    }

    /**
     * Upload a new revision. Restricted to the document's author or a
     * project manager — a reviewer must not be able to replace the file
     * they were asked to check.
     */
    public function uploadRevision(User $user, Document $document): bool
    {
        if (! $user->can(Permissions::DOCUMENTS_UPLOAD_REVISION)) {
            return false;
        }

        return $document->created_by === $user->id
            || $user->can(Permissions::DOCUMENTS_DELETE);
    }

    public function submitForReview(User $user, Document $document): bool
    {
        return $user->can(Permissions::DOCUMENTS_SUBMIT_REVIEW);
    }

    public function archive(User $user, Document $document): bool
    {
        return $user->can(Permissions::DOCUMENTS_ARCHIVE);
    }
}
