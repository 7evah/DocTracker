<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Models\Approval;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Notifications\ApprovalRequested;
use App\Notifications\DocumentDecided;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The approval circuit (§8, §24).
 *
 * Approvals are strictly sequential: exactly one step is active at a time,
 * and a step only becomes active once the previous one is approved. That
 * ordering is the whole point of the module, so it lives here rather than
 * being reconstructed by each component.
 */
class ApprovalService
{
    /**
     * Instantiate the applicable workflow onto a revision.
     *
     * Called when every review on the revision has come back favourable.
     * Returns null when no workflow applies, which the caller treats as
     * "nothing left to sign" and approves the document outright.
     *
     * @return Collection<int, Approval>|null
     */
    public function start(DocumentVersion $version, ?User $actor = null): ?Collection
    {
        $document = $version->document;
        $workflow = ApprovalWorkflow::resolveFor($document->project);

        if (! $workflow || $workflow->steps->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($version, $workflow, $actor, $document) {
            $created = collect();

            /*
            | Deadlines are computed up front and cumulatively, from each
            | step's own turnaround: step 1 is due in its own allowance,
            | step 2 in that plus its own, and so on. Reading the value from
            | the workflow we already resolved avoids matching a same-ordered
            | step belonging to some other workflow.
            */
            $cumulativeDays = 0;

            foreach ($workflow->steps as $step) {
                $cumulativeDays += $step->turnaround_days ?? 3;
                $deadline = now()->addDays($cumulativeDays);

                $approver = $this->resolveApprover($step, $version);

                // A step with nobody to sign it would stall the chain
                // forever, so it is skipped rather than left pending.
                $created->push($this->upsert(
                    $version,
                    $step,
                    $approver,
                    $approver ? ApprovalStatus::Pending : ApprovalStatus::Skipped,
                    $deadline,
                ));
            }

            activity('document')
                ->performedOn($document)
                ->causedBy($actor)
                ->event('approval_started')
                ->withProperties(['revision' => $version->revision])
                ->log('document.approval_started');

            // Activating the first signable step also sends its notification.
            $this->activateNextStep($version->fresh());

            return $created;
        });
    }

    /**
     * Record an approver's decision on their step, then advance the chain.
     */
    public function decide(
        Approval $approval,
        User $approver,
        bool $approved,
        ?string $comment = null,
    ): void {
        DB::transaction(function () use ($approval, $approver, $approved, $comment) {
            $approval->forceFill([
                'status' => $approved ? ApprovalStatus::Approved : ApprovalStatus::Rejected,
                'comment' => $comment ?: $approval->comment,
                'approved_at' => $approved ? now() : null,
                'rejected_at' => $approved ? null : now(),
            ])->save();

            $version = $approval->documentVersion;
            $document = $version->document;

            activity('document')
                ->performedOn($document)
                ->causedBy($approver)
                ->event($approved ? 'approval_approved' : 'approval_rejected')
                ->withProperties([
                    'revision' => $version->revision,
                    'step' => $approval->step,
                ])
                ->log($approved ? 'document.approval_approved' : 'document.approval_rejected');

            if (! $approved) {
                $this->abandon($version, $document, $approver);

                return;
            }

            $this->activateNextStep($version->fresh());
        });
    }

    /**
     * Promote the lowest-numbered still-pending step to active, or finish the
     * document when every step is settled.
     */
    private function activateNextStep(DocumentVersion $version): void
    {
        $next = $version->approvals()
            ->where('status', ApprovalStatus::Pending)
            ->orderBy('step')
            ->first();

        if (! $next) {
            $this->complete($version);

            return;
        }

        $next->forceFill([
            'status' => ApprovalStatus::InProgress,
            'assigned_at' => now(),
        ])->save();

        $next->approver?->notify(new ApprovalRequested($next));
    }

    /** Every step settled favourably — the document is approved (§7). */
    private function complete(DocumentVersion $version): void
    {
        $document = $version->document;

        $document->forceFill(['status' => DocumentStatus::Approved])->save();

        activity('document')
            ->performedOn($document)
            ->causedBy(null)
            ->event('approved')
            ->withProperties(['revision' => $version->revision])
            ->log('document.approved');

        $document->creator?->notify(new DocumentDecided($document, $version, approved: true));
    }

    /** A rejection stops the circuit; later steps are skipped, not left pending. */
    private function abandon(DocumentVersion $version, $document, User $approver): void
    {
        $version->approvals()
            ->whereIn('status', [ApprovalStatus::Pending, ApprovalStatus::InProgress])
            ->update(['status' => ApprovalStatus::Skipped]);

        $document->forceFill(['status' => DocumentStatus::Rejected])->save();

        $document->creator?->notify(new DocumentDecided($document, $version, approved: false));
    }

    /** The step currently awaiting a signature, if any. */
    public function currentStep(DocumentVersion $version): ?Approval
    {
        return $version->approvals()
            ->where('status', ApprovalStatus::InProgress)
            ->orderBy('step')
            ->first();
    }

    /**
     * Pick the user who signs a given step.
     *
     * For the project-manager step the project's own manager is the obvious
     * answer; otherwise the first active holder of the step's role. A more
     * elaborate rule (round-robin, per-project overrides) can replace this
     * without touching the chain logic.
     */
    private function resolveApprover(ApprovalWorkflowStep $step, DocumentVersion $version): ?User
    {
        $project = $version->document->project;

        if ($step->role === UserRole::ProjectManager->value && $project?->manager) {
            return $project->manager;
        }

        return User::query()
            ->active()
            ->role($step->role)
            ->orderBy('id')
            ->first();
    }

    private function upsert(
        DocumentVersion $version,
        ApprovalWorkflowStep $step,
        ?User $approver,
        ApprovalStatus $status,
        $deadline,
    ): Approval {
        return Approval::updateOrCreate(
            [
                'document_version_id' => $version->id,
                'step' => $step->step_order,
            ],
            [
                'approver_id' => $approver?->id,
                'role' => $step->role,
                'status' => $status,
                'deadline' => $deadline,
                'comment' => null,
                'approved_at' => null,
                'rejected_at' => null,
            ],
        );
    }
}
