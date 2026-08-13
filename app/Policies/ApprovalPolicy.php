<?php

namespace App\Policies;

use App\Enums\ApprovalStatus;
use App\Models\Approval;
use App\Models\User;
use App\Support\Permissions;

/**
 * Authorization for approval steps (§13, §24).
 *
 * The defining rule is sequence: a step may only be signed by its assigned
 * approver, and only while it is the active step. An approver further down
 * the circuit cannot sign ahead of their turn even though they hold the same
 * permission.
 */
class ApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::APPROVALS_VIEW);
    }

    public function view(User $user, Approval $approval): bool
    {
        return $user->can(Permissions::APPROVALS_VIEW);
    }

    /**
     * Signing a step is authorised by the assignment itself, not by a global
     * permission.
     *
     * A circuit assigns each step to a different role — the §8 example runs
     * Reviewer, then Project Manager, then Approver — so no single permission
     * covers every signer. Requiring documents.approve here would break the
     * reviewer step outright, since that role holds documents.review instead.
     *
     * The pairing of "assigned to you" with "currently the active step" is a
     * narrower grant than a role permission anyway: it names one person, on
     * one step, at one moment. `approvals.view` still gates the module.
     */
    public function approve(User $user, Approval $approval): bool
    {
        return $this->isTheirActiveStep($user, $approval);
    }

    public function reject(User $user, Approval $approval): bool
    {
        return $this->isTheirActiveStep($user, $approval);
    }

    /**
     * Assigned to this user, and currently the active step. InProgress is set
     * by ApprovalService only once every earlier step has been approved, so
     * checking it here is what enforces the ordering — an approver further
     * down the circuit cannot sign ahead of their turn.
     */
    private function isTheirActiveStep(User $user, Approval $approval): bool
    {
        return $approval->approver_id !== null
            && $approval->approver_id === $user->id
            && $approval->status === ApprovalStatus::InProgress
            && $user->status->canLogin();
    }
}
