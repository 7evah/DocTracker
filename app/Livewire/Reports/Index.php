<?php

namespace App\Livewire\Reports;

use App\Enums\ReportType;
use App\Models\Discipline;
use App\Models\Project;
use App\Services\ReportService;
use App\Support\Permissions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginated;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public int $perPage = 15;

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

    /*
    | Switching report or narrowing a filter produces a different, usually
    | shorter result set, so staying on page 6 would strand the user on a
    | blank table.
    */
    public function updated(string $property): void
    {
        if (in_array($property, ['report', 'project', 'discipline', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Paginate the report's rows for display only.
     *
     * ReportService returns plain arrays rather than a query — the grouped
     * reports are assembled in PHP — so this slices in memory. The exception
     * reports (overdue reviews and approvals) are the ones that grow with the
     * data and would otherwise render every row at once (§28). The export
     * route builds its own ReportResult and is deliberately untouched: a CSV
     * of page 1 of 40 would be worse than useless.
     *
     * @param  list<list<string|int|float|null>>  $rows
     * @return LengthAwarePaginator<int, list<string|int|float|null>>
     */
    private function paginateRows(array $rows): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();

        return new Paginated(
            array_slice($rows, ($page - 1) * $this->perPage, $this->perPage),
            count($rows),
            $this->perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()],
        );
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

        $result = $reports->build($type, $this->filters());

        return view('livewire.reports.index', [
            'result' => $result,
            'rows' => $this->paginateRows($result->rows),
            'type' => $type,
            'types' => ReportType::cases(),
            'projects' => Project::query()->orderBy('project_code')->pluck('project_code', 'id'),
            'disciplines' => Discipline::options(),
        ])->title(__('reports.title'));
    }
}
