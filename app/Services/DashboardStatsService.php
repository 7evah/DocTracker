<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Enums\ReviewStatus;
use App\Models\Approval;
use App\Models\Document;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;

/**
 * Builds the dashboard KPI set (§17).
 *
 * Kept out of the Livewire component so the same numbers can be reused by
 * reports and exports later without duplicating query logic (§5).
 */
class DashboardStatsService
{
    public function __construct(private readonly User $user) {}

    public static function for(User $user): self
    {
        return new self($user);
    }

    /** @return array<string, int> */
    public function totals(): array
    {
        return [
            'projects' => Project::query()->open()->count(),
            'documents' => Document::query()->count(),
            'pending_reviews' => Review::query()->open()->count(),
            'pending_approvals' => Approval::query()->open()->count(),
            'approved_documents' => Document::query()->where('status', DocumentStatus::Approved)->count(),
            'needs_revision' => Document::query()->where('status', DocumentStatus::NeedsRevision)->count(),
            'overdue_reviews' => Review::query()->overdue()->count(),
            'overdue_approvals' => Approval::query()->overdue()->count(),
        ];
    }

    /**
     * The same KPIs narrowed to the signed-in user's own workload, used for
     * the reviewer and engineer views of the dashboard.
     *
     * @return array<string, int>
     */
    public function mine(): array
    {
        return [
            'my_pending_reviews' => Review::query()
                ->where('reviewer_id', $this->user->id)
                ->whereIn('status', ReviewStatus::openValues())
                ->count(),
            'my_overdue_reviews' => Review::query()
                ->where('reviewer_id', $this->user->id)
                ->overdue()
                ->count(),
            'my_pending_approvals' => Approval::query()
                ->where('approver_id', $this->user->id)
                ->whereIn('status', ApprovalStatus::openValues())
                ->count(),
            'my_open_tasks' => $this->user->tasks()->open()->count(),
        ];
    }
}
