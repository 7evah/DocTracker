<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;

/**
 * Authorization for user administration (§29).
 *
 * Administrators bypass these via Gate::before, which is exactly why the
 * self-lockout guards live on the model (User::canBeDeactivatedBy and
 * friends) rather than here — an admin must not be able to lock themselves
 * or the whole installation out, and a policy check would not stop them.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::USERS_MANAGE);
    }

    public function view(User $user, User $target): bool
    {
        return $user->can(Permissions::USERS_MANAGE);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::USERS_MANAGE);
    }

    public function update(User $user, User $target): bool
    {
        return $user->can(Permissions::USERS_MANAGE);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can(Permissions::USERS_MANAGE);
    }

    /** Changing roles and permissions is the same privilege as managing users. */
    public function assignRoles(User $user, User $target): bool
    {
        return $user->can(Permissions::USERS_MANAGE);
    }

    public function manageRoles(User $user): bool
    {
        return $user->can(Permissions::USERS_MANAGE);
    }

    public function manageDisciplines(User $user): bool
    {
        return $user->can(Permissions::DISCIPLINES_MANAGE);
    }

    public function manageWorkflows(User $user): bool
    {
        return $user->can(Permissions::WORKFLOWS_MANAGE);
    }

    public function manageSettings(User $user): bool
    {
        return $user->can(Permissions::SETTINGS_MANAGE);
    }
}
