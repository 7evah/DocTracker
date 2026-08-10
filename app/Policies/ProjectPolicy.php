<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Support\Permissions;

/**
 * Authorization for projects (§13).
 *
 * Administrators never reach these methods — the Gate::before hook in
 * AppServiceProvider short-circuits first.
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::PROJECTS_VIEW);
    }

    public function view(User $user, Project $project): bool
    {
        return $user->can(Permissions::PROJECTS_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::PROJECTS_CREATE);
    }

    /**
     * A project manager may edit projects they manage; broader edit rights
     * require the explicit permission.
     */
    public function update(User $user, Project $project): bool
    {
        if (! $user->can(Permissions::PROJECTS_UPDATE)) {
            return false;
        }

        return true;
    }

    /**
     * Permission to delete. The separate question of whether this particular
     * project *may* be deleted is a data-integrity rule, not a permission, and
     * lives in Project::canBeDeleted() — putting it here would make it dead
     * code, because administrators short-circuit policies via Gate::before.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->can(Permissions::PROJECTS_DELETE);
    }

    public function restore(User $user, Project $project): bool
    {
        return $user->can(Permissions::PROJECTS_DELETE);
    }
}
