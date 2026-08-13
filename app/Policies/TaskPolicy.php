<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Support\Permissions;

/**
 * Authorization for tasks (§13, §27).
 *
 * Tasks are collaborative rather than hierarchical: the person a task is
 * assigned to owns its progress, and the person who raised it owns its
 * wording. Both are recognised here.
 */
class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::TASKS_VIEW);
    }

    public function view(User $user, Task $task): bool
    {
        return $user->can(Permissions::TASKS_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::TASKS_CREATE);
    }

    /**
     * Editing the task itself — wording, assignee, due date. Restricted to
     * the raiser and the assignee, so an unrelated colleague cannot rewrite
     * someone else's action item.
     */
    public function update(User $user, Task $task): bool
    {
        if (! $user->can(Permissions::TASKS_UPDATE)) {
            return false;
        }

        return $task->created_by === $user->id
            || $task->assigned_to === $user->id;
    }

    /**
     * Completion is a separate permission from editing, but obeys the same
     * relationship rule.
     *
     * The distinction is which permission you hold, not who you are: an
     * Approver has tasks.complete without tasks.update, so they can close out
     * work assigned to them but cannot reword or reassign it.
     *
     * There is deliberately no "or holds tasks.update" escape hatch here —
     * Engineers and Reviewers hold that permission too, so it would have let
     * any engineer close any colleague's task. Administrators still bypass
     * this via Gate::before.
     */
    public function complete(User $user, Task $task): bool
    {
        if (! $user->can(Permissions::TASKS_COMPLETE)) {
            return false;
        }

        return $task->assigned_to === $user->id
            || $task->created_by === $user->id;
    }

    /** Only the raiser may delete; everyone else cancels instead (§34). */
    public function delete(User $user, Task $task): bool
    {
        return $user->can(Permissions::TASKS_UPDATE)
            && $task->created_by === $user->id;
    }
}
