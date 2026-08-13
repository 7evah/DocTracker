<?php

namespace App\Enums;

/**
 * The report catalogue (§28).
 *
 * Each case names a method on ReportService, so adding a report means adding
 * a case and a method — nothing else in the UI or the export path changes.
 */
enum ReportType: string
{
    case DocumentStatusSummary = 'document_status_summary';
    case DocumentsByProject = 'documents_by_project';
    case DocumentsByDiscipline = 'documents_by_discipline';
    case ProjectProgress = 'project_progress';
    case ReviewDelays = 'review_delays';
    case ApprovalPerformance = 'approval_performance';
    case OverdueReviews = 'overdue_reviews';
    case OverdueApprovals = 'overdue_approvals';
    case UserWorkload = 'user_workload';

    public function label(): string
    {
        return __("reports.types.{$this->value}.label");
    }

    public function description(): string
    {
        return __("reports.types.{$this->value}.description");
    }

    public function icon(): string
    {
        return match ($this) {
            self::DocumentStatusSummary => 'chart-pie',
            self::DocumentsByProject => 'folder',
            self::DocumentsByDiscipline => 'squares-2x2',
            self::ProjectProgress => 'chart-bar',
            self::ReviewDelays => 'clock',
            self::ApprovalPerformance => 'check-badge',
            self::OverdueReviews => 'exclamation-triangle',
            self::OverdueApprovals => 'exclamation-triangle',
            self::UserWorkload => 'users',
        };
    }

    /** Reports that render a distribution worth charting. */
    public function hasChart(): bool
    {
        return in_array($this, [
            self::DocumentStatusSummary,
            self::DocumentsByDiscipline,
            self::DocumentsByProject,
        ], true);
    }

    /** Which shared filters this report actually honours. */
    public function usesProjectFilter(): bool
    {
        return ! in_array($this, [self::DocumentsByProject, self::UserWorkload], true);
    }

    public function usesDisciplineFilter(): bool
    {
        return in_array($this, [
            self::DocumentStatusSummary,
            self::DocumentsByProject,
            self::DocumentsByDiscipline,
            self::ProjectProgress,
        ], true);
    }

    /** The ReportService method that builds this report. */
    public function method(): string
    {
        return str($this->value)->camel()->toString();
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
