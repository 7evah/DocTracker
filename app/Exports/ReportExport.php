<?php

namespace App\Exports;

use App\Support\ReportResult;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One export class for every report (§28).
 *
 * Possible because ReportResult normalises all nine reports to headings plus
 * rows — a per-report export class would be nine copies of the same file.
 */
class ReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private readonly ReportResult $report) {}

    /** @return list<list<string|int|float|null>> */
    public function array(): array
    {
        return $this->report->rows;
    }

    /** @return list<string> */
    public function headings(): array
    {
        return $this->report->headings;
    }

    /** Excel caps sheet names at 31 characters and rejects several symbols. */
    public function title(): string
    {
        return str($this->report->title())->limit(28, '')->replace(['/', '\\', '?', '*', ':', '[', ']'], '-')->toString();
    }

    /** @return array<int, array<string, mixed>> */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '003A70'], // JESA primary (§15)
                ],
            ],
        ];
    }
}
