<?php

namespace App\Livewire\Reports;

use App\Enums\ReportType;
use App\Models\Discipline;
use App\Models\Project;
use App\Services\ReportService;
use App\Support\Permissions;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    #[Url(as: 'r', except: 'document_status_summary')]
    public string $report = 'document_status_summary';

    #[Url(except: '')]
    public string $project = '';

    #[Url(except: '')]
    public string $discipline = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permissions::REPORTS_VIEW), 403);
    }

    public function type(): ReportType
    {
        // A tampered query string falls back rather than erroring.
        return ReportType::tryFrom($this->report) ?? ReportType::DocumentStatusSummary;
    }

    public function canExport(): bool
    {
        return auth()->user()->can(Permissions::REPORTS_EXPORT);
    }

    public function resetFilters(): void
    {
        $this->reset('project', 'discipline', 'from', 'to');
    }

    public function hasFilters(): bool
    {
        return filled($this->project) || filled($this->discipline)
            || filled($this->from) || filled($this->to);
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        $type = $this->type();

        // Only pass filters the selected report honours, so the export URL
        // and the on-screen table can never disagree.
        return [
            'project' => $type->usesProjectFilter() ? ($this->project ?: null) : null,
            'discipline' => $type->usesDisciplineFilter() ? ($this->discipline ?: null) : null,
            'from' => $this->from ?: null,
            'to' => $this->to ?: null,
        ];
    }

    /** Export URL carrying the exact filter set currently on screen (§28). */
    public function exportUrl(string $format): string
    {
        return route('reports.export', array_filter([
            'report' => $this->type()->value,
            'format' => $format,
        ] + $this->filters(), fn ($value) => $value !== null));
    }

    public function render(ReportService $reports): View
    {
        $type = $this->type();

        return view('livewire.reports.index', [
            'result' => $reports->build($type, $this->filters()),
            'type' => $type,
            'types' => ReportType::cases(),
            'projects' => Project::query()->orderBy('project_code')->pluck('project_code', 'id'),
            'disciplines' => Discipline::options(),
        ])->title(__('reports.title'));
    }
}
