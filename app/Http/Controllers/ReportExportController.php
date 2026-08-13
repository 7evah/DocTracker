<?php

namespace App\Http\Controllers;

use App\Enums\ReportType;
use App\Exports\ReportExport;
use App\Models\Discipline;
use App\Models\Project;
use App\Services\ReportService;
use App\Support\Permissions;
use App\Support\ReportResult;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Excel and PDF export of any report (§28).
 *
 * Filters arrive in the query string exactly as the page holds them, so an
 * export always reflects what the user is looking at rather than the whole
 * unfiltered dataset.
 */
class ReportExportController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): Response
    {
        // Exporting is separately permissioned from viewing (§13): a report
        // that is fine on screen may not be fine leaving the building, so
        // reports.view does not imply reports.export.
        abort_unless($request->user()->can(Permissions::REPORTS_EXPORT), 403);

        $validated = $request->validate([
            'report' => ['required', Rule::enum(ReportType::class)],
            'format' => ['required', Rule::in(['xlsx', 'pdf'])],
            'project' => ['nullable', Rule::exists('projects', 'id')],
            'discipline' => ['nullable', Rule::exists('disciplines', 'id')],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $type = ReportType::from($validated['report']);

        $filters = [
            'project' => $validated['project'] ?? null,
            'discipline' => $validated['discipline'] ?? null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ];

        $report = $reports->build($type, $filters);

        return $validated['format'] === 'xlsx'
            ? $this->excel($report)
            : $this->pdf($report, $filters);
    }

    private function excel(ReportResult $report): Response
    {
        return Excel::download(new ReportExport($report), $report->filename().'.xlsx');
    }

    /** @param array<string, mixed> $filters */
    private function pdf(ReportResult $report, array $filters): Response
    {
        $pdf = Pdf::loadView('reports.pdf', [
            'report' => $report,
            'generatedBy' => auth()->user()->name,
            'filterSummary' => $this->describeFilters($filters),
        ]);

        // Wide reports are unreadable portrait; the row count is what matters.
        $pdf->setPaper('a4', count($report->headings) > 6 ? 'landscape' : 'portrait');

        return $pdf->download($report->filename().'.pdf');
    }

    /**
     * Human-readable filter summary printed on the PDF, so a shared export
     * cannot be mistaken for the full dataset.
     *
     * @param  array<string, mixed>  $filters
     */
    private function describeFilters(array $filters): string
    {
        $parts = [];

        if ($filters['project'] ?? null) {
            $parts[] = __('documents.fields.project').' : '
                .(Project::find($filters['project'])?->project_code ?? $filters['project']);
        }

        if ($filters['discipline'] ?? null) {
            $parts[] = __('documents.fields.discipline').' : '
                .(Discipline::find($filters['discipline'])?->name ?? $filters['discipline']);
        }

        if ($filters['from'] ?? null) {
            $parts[] = __('reports.filters.from').' '.$filters['from'];
        }

        if ($filters['to'] ?? null) {
            $parts[] = __('reports.filters.to').' '.$filters['to'];
        }

        return implode(' · ', $parts);
    }
}
