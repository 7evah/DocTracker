<?php

namespace App\Support;

use App\Enums\ReportType;

/**
 * A rendered report: column headings, rows, and an optional distribution for
 * charting.
 *
 * Every report normalises to this shape so the table view, the Excel export
 * and the PDF export all consume one structure rather than each knowing the
 * internals of nine different queries (§28).
 */
final class ReportResult
{
    /**
     * @param  list<string>  $headings
     * @param  list<list<string|int|float|null>>  $rows
     * @param  array<string, int|float>  $chart  label => value
     * @param  array<string, string>  $summary  caption => value, shown above the table
     */
    public function __construct(
        public readonly ReportType $type,
        public readonly array $headings,
        public readonly array $rows,
        public readonly array $chart = [],
        public readonly array $summary = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->rows === [];
    }

    public function title(): string
    {
        return $this->type->label();
    }

    /** Largest charted value, used to scale bar widths. */
    public function chartMax(): float
    {
        return $this->chart === [] ? 0.0 : (float) max($this->chart);
    }

    /** Filename stem for exports, e.g. "docflow-review-delays-2026-08-11". */
    public function filename(): string
    {
        return 'docflow-'.str_replace('_', '-', $this->type->value).'-'.now()->format('Y-m-d');
    }
}
