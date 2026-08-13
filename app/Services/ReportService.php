<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Enums\ReportType;
use App\Enums\ReviewStatus;
use App\Enums\TaskStatus;
use App\Models\Approval;
use App\Models\Discipline;
use App\Models\Document;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use App\Support\ReportResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds every report in the catalogue (§28).
 *
 * Durations (turnaround, delay) are averaged in PHP rather than with SQL date
 * functions: TIMESTAMPDIFF and its equivalents differ between MariaDB and the
 * SQLite used by the test suite, and the row counts a prototype deals with do
 * not justify a database-specific query. If a report ever outgrows that, it is
 * the one place to revisit.
 */
class ReportService
{
    /** @param array{project?: string|int|null, discipline?: string|int|null, from?: string|null, to?: string|null} $filters */
    public function build(ReportType $type, array $filters = []): ReportResult
    {
        $method = $type->method();

        return $this->{$method}($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Distribution reports
    |--------------------------------------------------------------------------
    */

    /** @param array<string, mixed> $filters */
    public function documentStatusSummary(array $filters): ReportResult
    {
        $counts = $this->documents($filters)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $rows = [];
        $chart = [];
        $total = (int) $counts->sum();

        foreach (DocumentStatus::cases() as $status) {
            $value = (int) ($counts[$status->value] ?? 0);
            $share = $total > 0 ? round($value / $total * 100, 1) : 0.0;

            $rows[] = [$status->label(), $value, $share.' %'];
            $chart[$status->label()] = $value;
        }

        return new ReportResult(
            type: ReportType::DocumentStatusSummary,
            headings: [__('common.labels.status'), __('reports.headings.count'), __('reports.headings.share')],
            rows: $rows,
            chart: $chart,
            summary: [__('reports.headings.total') => (string) $total],
        );
    }

    /** @param array<string, mixed> $filters */
    public function documentsByProject(array $filters): ReportResult
    {
        $projects = Project::query()
            ->withCount([
                'documents' => fn (Builder $q) => $this->applyDocumentFilters($q, $filters),
                'documents as approved_count' => fn (Builder $q) => $this->applyDocumentFilters($q, $filters)
                    ->where('status', DocumentStatus::Approved),
                'documents as pending_count' => fn (Builder $q) => $this->applyDocumentFilters($q, $filters)
                    ->whereIn('status', [
                        DocumentStatus::Draft->value,
                        DocumentStatus::UnderReview->value,
                        DocumentStatus::NeedsRevision->value,
                    ]),
            ])
            ->orderByDesc('documents_count')
            ->get();

        $rows = [];
        $chart = [];

        foreach ($projects as $project) {
            $rows[] = [
                $project->project_code,
                $project->name,
                $project->documents_count,
                $project->approved_count,
                $project->pending_count,
            ];

            $chart[$project->project_code] = $project->documents_count;
        }

        return new ReportResult(
            type: ReportType::DocumentsByProject,
            headings: [
                __('projects.fields.project_code'),
                __('projects.fields.name'),
                __('reports.headings.documents'),
                __('reports.headings.approved'),
                __('reports.headings.pending'),
            ],
            rows: $rows,
            chart: $chart,
        );
    }

    /** @param array<string, mixed> $filters */
    public function documentsByDiscipline(array $filters): ReportResult
    {
        $disciplines = Discipline::query()
            ->withCount([
                'documents' => fn (Builder $q) => $this->applyDocumentFilters($q, $filters),
                'documents as approved_count' => fn (Builder $q) => $this->applyDocumentFilters($q, $filters)
                    ->where('status', DocumentStatus::Approved),
            ])
            ->ordered()
            ->get();

        $rows = [];
        $chart = [];

        foreach ($disciplines as $discipline) {
            $rows[] = [
                $discipline->code,
                $discipline->name,
                $discipline->documents_count,
                $discipline->approved_count,
            ];

            if ($discipline->documents_count > 0) {
                $chart[$discipline->code] = $discipline->documents_count;
            }
        }

        return new ReportResult(
            type: ReportType::DocumentsByDiscipline,
            headings: [
                __('reports.headings.code'),
                __('common.labels.discipline'),
                __('reports.headings.documents'),
                __('reports.headings.approved'),
            ],
            rows: $rows,
            chart: $chart,
        );
    }

    /** @param array<string, mixed> $filters */
    public function projectProgress(array $filters): ReportResult
    {
        $projects = Project::query()
            ->with('manager:id,name')
            ->withListingCounts()
            ->when($filters['project'] ?? null, fn (Builder $q, $id) => $q->whereKey($id))
            ->orderBy('project_code')
            ->get();

        $rows = [];

        foreach ($projects as $project) {
            $rows[] = [
                $project->project_code,
                $project->name,
                $project->status->label(),
                $project->manager?->name ?? __('projects.no_manager'),
                $project->documents_count,
                $project->approved_documents_count,
                ($project->documentProgress() ?? 0).' %',
                $project->end_date?->format('d/m/Y') ?? '—',
                $project->isOverdue() ? __('common.labels.overdue') : '',
            ];
        }

        return new ReportResult(
            type: ReportType::ProjectProgress,
            headings: [
                __('projects.fields.project_code'),
                __('projects.fields.name'),
                __('common.labels.status'),
                __('projects.fields.manager'),
                __('reports.headings.documents'),
                __('reports.headings.approved'),
                __('projects.stats.progress'),
                __('projects.fields.end_date'),
                '',
            ],
            rows: $rows,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Performance reports
    |--------------------------------------------------------------------------
    */

    /** @param array<string, mixed> $filters */
    public function reviewDelays(array $filters): ReportResult
    {
        $reviews = $this->reviews($filters)
            ->with('reviewer:id,name')
            ->get();

        $rows = [];

        foreach ($reviews->groupBy('reviewer_id') as $group) {
            $reviewer = $group->first()->reviewer;

            $completed = $group->filter(fn (Review $r) => $r->reviewed_at !== null);
            $open = $group->filter(fn (Review $r) => $r->status->isOpen());
            $overdue = $group->filter(fn (Review $r) => $r->isOverdue());

            // Late = finished after its own deadline, distinct from "overdue"
            // which only describes work still outstanding.
            $late = $completed->filter(
                fn (Review $r) => $r->deadline !== null && $r->reviewed_at->greaterThan($r->deadline)
            );

            $rows[] = [
                $reviewer?->name ?? '—',
                $group->count(),
                $completed->count(),
                $open->count(),
                $overdue->count(),
                $this->averageDays($completed, 'assigned_at', 'reviewed_at'),
                $late->count(),
            ];
        }

        usort($rows, fn (array $a, array $b) => $b[1] <=> $a[1]);

        return new ReportResult(
            type: ReportType::ReviewDelays,
            headings: [
                __('reviews.fields.reviewer'),
                __('reports.headings.assigned'),
                __('reports.headings.completed'),
                __('reports.headings.open'),
                __('reports.headings.overdue'),
                __('reports.headings.avg_days'),
                __('reports.headings.late'),
            ],
            rows: $rows,
        );
    }

    /** @param array<string, mixed> $filters */
    public function approvalPerformance(array $filters): ReportResult
    {
        $approvals = $this->approvals($filters)
            ->with('approver:id,name')
            ->get();

        $rows = [];

        foreach ($approvals->groupBy('approver_id') as $group) {
            $approver = $group->first()->approver;

            $signed = $group->filter(fn (Approval $a) => $a->approved_at !== null);
            $rejected = $group->filter(fn (Approval $a) => $a->status === ApprovalStatus::Rejected);
            $open = $group->filter(fn (Approval $a) => $a->status->isOpen());
            $overdue = $group->filter(fn (Approval $a) => $a->isOverdue());

            $rows[] = [
                $approver?->name ?? '—',
                $group->count(),
                $signed->count(),
                $rejected->count(),
                $open->count(),
                $overdue->count(),
                $this->averageDays($signed, 'assigned_at', 'approved_at'),
            ];
        }

        usort($rows, fn (array $a, array $b) => $b[1] <=> $a[1]);

        return new ReportResult(
            type: ReportType::ApprovalPerformance,
            headings: [
                __('approvals.fields.approver'),
                __('reports.headings.assigned'),
                __('reports.headings.approved'),
                __('reports.headings.rejected'),
                __('reports.headings.open'),
                __('reports.headings.overdue'),
                __('reports.headings.avg_days'),
            ],
            rows: $rows,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Exception reports
    |--------------------------------------------------------------------------
    */

    /** @param array<string, mixed> $filters */
    public function overdueReviews(array $filters): ReportResult
    {
        $reviews = $this->reviews($filters)
            ->overdue()
            ->with(['reviewer:id,name', 'documentVersion.document.project:id,project_code'])
            ->orderBy('deadline')
            ->get();

        $rows = $reviews->map(fn (Review $review) => [
            $review->documentVersion?->document?->document_number ?? '—',
            $review->documentVersion?->document?->title ?? '—',
            $review->documentVersion?->document?->project?->project_code ?? '—',
            $review->reviewer?->name ?? '—',
            $review->priority->label(),
            $review->deadline?->format('d/m/Y') ?? '—',
            $this->daysLate($review->deadline),
        ])->all();

        return new ReportResult(
            type: ReportType::OverdueReviews,
            headings: [
                __('documents.fields.document_number'),
                __('documents.fields.title'),
                __('documents.fields.project'),
                __('reviews.fields.reviewer'),
                __('common.labels.priority'),
                __('reviews.fields.deadline'),
                __('reports.headings.days_late'),
            ],
            rows: $rows,
            summary: [__('reports.headings.total') => (string) count($rows)],
        );
    }

    /** @param array<string, mixed> $filters */
    public function overdueApprovals(array $filters): ReportResult
    {
        $approvals = $this->approvals($filters)
            ->overdue()
            ->with(['approver:id,name', 'documentVersion.document.project:id,project_code'])
            ->orderBy('deadline')
            ->get();

        $rows = $approvals->map(fn (Approval $approval) => [
            $approval->documentVersion?->document?->document_number ?? '—',
            $approval->documentVersion?->document?->title ?? '—',
            $approval->documentVersion?->document?->project?->project_code ?? '—',
            $approval->approver?->name ?? '—',
            (string) $approval->step,
            $approval->deadline?->format('d/m/Y') ?? '—',
            $this->daysLate($approval->deadline),
        ])->all();

        return new ReportResult(
            type: ReportType::OverdueApprovals,
            headings: [
                __('documents.fields.document_number'),
                __('documents.fields.title'),
                __('documents.fields.project'),
                __('approvals.fields.approver'),
                __('approvals.fields.step'),
                __('approvals.fields.deadline'),
                __('reports.headings.days_late'),
            ],
            rows: $rows,
            summary: [__('reports.headings.total') => (string) count($rows)],
        );
    }

    /** @param array<string, mixed> $filters */
    public function userWorkload(array $filters): ReportResult
    {
        $users = User::query()
            ->active()
            ->with('roles')
            ->withCount([
                'reviews as open_reviews_count' => fn ($q) => $q->whereIn('status', ReviewStatus::openValues()),
                'approvals as open_approvals_count' => fn ($q) => $q->whereIn('status', ApprovalStatus::openValues()),
                'tasks as open_tasks_count' => fn ($q) => $q->whereIn('status', TaskStatus::openValues()),
                'createdDocuments as documents_count',
            ])
            ->orderBy('name')
            ->get();

        $rows = $users->map(fn (User $user) => [
            $user->name,
            $user->department ?? '—',
            $user->primaryRole() ? __('enums.role.'.$user->primaryRole()) : '—',
            $user->open_reviews_count,
            $user->open_approvals_count,
            $user->open_tasks_count,
            $user->documents_count,
            $user->open_reviews_count + $user->open_approvals_count + $user->open_tasks_count,
        ])->all();

        return new ReportResult(
            type: ReportType::UserWorkload,
            headings: [
                __('common.labels.name'),
                __('common.labels.department'),
                __('common.labels.role'),
                __('reports.headings.open_reviews'),
                __('reports.headings.open_approvals'),
                __('reports.headings.open_tasks'),
                __('reports.headings.documents_created'),
                __('reports.headings.total_open'),
            ],
            rows: $rows,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Filtered base queries
    |--------------------------------------------------------------------------
    */

    /** @param array<string, mixed> $filters */
    private function documents(array $filters): Builder
    {
        return $this->applyDocumentFilters(Document::query(), $filters);
    }

    /** @param array<string, mixed> $filters */
    private function applyDocumentFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['project'] ?? null, fn (Builder $q, $id) => $q->where('project_id', $id))
            ->when($filters['discipline'] ?? null, fn (Builder $q, $id) => $q->where('discipline_id', $id))
            ->when($filters['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
    }

    /** @param array<string, mixed> $filters */
    private function reviews(array $filters): Builder
    {
        return Review::query()
            ->when($filters['project'] ?? null, fn (Builder $q, $id) => $q->whereHas(
                'documentVersion.document',
                fn (Builder $d) => $d->where('project_id', $id),
            ))
            ->when($filters['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('assigned_at', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $q, $date) => $q->whereDate('assigned_at', '<=', $date));
    }

    /** @param array<string, mixed> $filters */
    private function approvals(array $filters): Builder
    {
        return Approval::query()
            ->when($filters['project'] ?? null, fn (Builder $q, $id) => $q->whereHas(
                'documentVersion.document',
                fn (Builder $d) => $d->where('project_id', $id),
            ))
            ->when($filters['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('assigned_at', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $q, $date) => $q->whereDate('assigned_at', '<=', $date));
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Mean whole days between two timestamps across a collection, to one
     * decimal. Returns an em dash when nothing has completed yet, which reads
     * better in both the table and the exports than a misleading 0.
     *
     * @param  Collection<int, object>  $items
     */
    private function averageDays($items, string $startAttribute, string $endAttribute): string
    {
        $durations = $items
            ->filter(fn ($item) => $item->{$startAttribute} !== null && $item->{$endAttribute} !== null)
            ->map(fn ($item) => $item->{$startAttribute}->diffInDays($item->{$endAttribute}));

        if ($durations->isEmpty()) {
            return '—';
        }

        return (string) round($durations->avg(), 1);
    }

    private function daysLate(?Carbon $deadline): string
    {
        return $deadline === null ? '—' : (string) $deadline->diffInDays(now());
    }
}
