<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Models\Approval;
use App\Models\ApprovalWorkflow;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApprovalSeeder extends Seeder
{
    /**
     * Approval circuits consistent with each document's status (§35).
     *
     * Documents already Approved get a fully-signed chain; documents that
     * cleared review but are still Under Review get a partially-signed one,
     * so the stepper has a visible "current step" to demonstrate (§24).
     */
    public function run(): void
    {
        $workflow = ApprovalWorkflow::with('steps')->where('is_default', true)->first();

        if (! $workflow || $workflow->steps->isEmpty()) {
            return;
        }

        $approvers = [
            UserRole::Reviewer->value => User::role(UserRole::Reviewer->value)->first(),
            UserRole::ProjectManager->value => User::role(UserRole::ProjectManager->value)->first(),
            UserRole::Approver->value => User::role(UserRole::Approver->value)->first(),
        ];

        $documents = Document::with('versions')
            ->whereIn('status', [DocumentStatus::Approved, DocumentStatus::UnderReview])
            ->get();

        foreach ($documents as $index => $document) {
            $version = $document->versions->sortByDesc('id')->first();

            if (! $version) {
                continue;
            }

            // Approved documents are fully signed; the rest stop partway so
            // there is an active step to act on in the demo.
            $signedThrough = $document->status === DocumentStatus::Approved
                ? $workflow->steps->count()
                : $index % $workflow->steps->count();

            foreach ($workflow->steps as $position => $step) {
                $stepNumber = $step->step_order;
                $approver = $approvers[$step->role] ?? null;

                $status = match (true) {
                    $position < $signedThrough => ApprovalStatus::Approved,
                    $position === $signedThrough => ApprovalStatus::InProgress,
                    default => ApprovalStatus::Pending,
                };

                $assignedAt = $version->created_at?->copy()->addDays(3 + $position * 2) ?? now();

                Approval::updateOrCreate(
                    [
                        'document_version_id' => $version->id,
                        'step' => $stepNumber,
                    ],
                    [
                        'approver_id' => $approver?->id,
                        'role' => $step->role,
                        'status' => $status,
                        'assigned_at' => $status === ApprovalStatus::Pending ? null : $assignedAt,
                        // One overdue example so the queue's counter is real.
                        'deadline' => $index % 4 === 0 && $status === ApprovalStatus::InProgress
                            ? now()->subDays(2)
                            : $assignedAt->copy()->addDays($step->turnaround_days ?? 3),
                        'approved_at' => $status === ApprovalStatus::Approved ? $assignedAt->copy()->addDay() : null,
                        'comment' => $status === ApprovalStatus::Approved
                            ? 'Vu et approuvé pour cette étape.'
                            : null,
                    ],
                );
            }
        }
    }
}
