<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Enums\ReviewStatus;
use App\Enums\TaskStatus;
use App\Models\Approval;
use App\Models\Document;
use App\Models\Project;
use App\Models\Review;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

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
     * Site-wide recent activity for the dashboard timeline (§17, §34).
     *
     * @return Collection<int, Activity>
     */
    public function recentActivity(int $limit = 8): Collection
    {
        return Activity::query()
            ->with('causer:id,name,avatar_path')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Everything due soon that belongs to this user, merged into a single
     * chronological list (§17 "Upcoming Deadlines").
     *
     * Reviews, approvals and tasks are separate tables with different date
     * columns, so they are normalised to a common shape here rather than in
     * the view.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function upcomingDeadlines(int $withinDays = 14, int $limit = 8): Collection
    {
        $horizon = now()->addDays($withinDays);

        $reviews = Review::query()
            ->with('documentVersion.document:id,document_number,title')
            ->where('reviewer_id', $this->user->id)
            ->whereIn('status', ReviewStatus::openValues())
            ->whereNotNull('deadline')
            ->where('deadline', '<=', $horizon)
            ->get()
            ->map(fn (Review $review) => [
                'kind' => __('reviews.singular'),
                'icon' => 'eye',
                'label' => $review->documentVersion?->document?->document_number ?? '—',
                'detail' => $review->documentVersion?->document?->title,
                'due' => $review->deadline,
                'overdue' => $review->isOverdue(),
                'url' => route('reviews.show', $review),
            ]);

        $approvals = Approval::query()
            ->with('documentVersion.document:id,document_number,title')
            ->where('approver_id', $this->user->id)
            ->whereIn('status', ApprovalStatus::openValues())
            ->whereNotNull('deadline')
            ->where('deadline', '<=', $horizon)
            ->get()
            ->map(fn (Approval $approval) => [
                'kind' => __('approvals.singular'),
                'icon' => 'check-badge',
                'label' => $approval->documentVersion?->document?->document_number ?? '—',
                'detail' => $approval->documentVersion?->document?->title,
                'due' => $approval->deadline,
                'overdue' => $approval->isOverdue(),
                'url' => $approval->documentVersion?->document
                    ? route('documents.show', $approval->documentVersion->document).'?tab=approvals'
                    : null,
            ]);

        $tasks = Task::query()
            ->where('assigned_to', $this->user->id)
            ->whereIn('status', TaskStatus::openValues())
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $horizon->toDateString())
            ->get()
            ->map(fn (Task $task) => [
                'kind' => __('tasks.singular'),
                'icon' => 'clipboard-document-check',
                'label' => $task->title,
                'detail' => null,
                'due' => $task->due_date,
                'overdue' => $task->isOverdue(),
                'url' => route('tasks.index'),
            ]);

        return $reviews->concat($approvals)->concat($tasks)
            ->sortBy('due')
            ->take($limit)
            ->values();
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
