{{--
    PDF layout for any report (§28).

    Deliberately self-contained: DomPDF has no access to the Vite build, so
    styling is inline rather than Tailwind, and the palette is written out in
    hex to match the JESA branding (§15).
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $report->title() }}</title>
    <style>
        @page { margin: 24mm 14mm 20mm 14mm; }

        body {
            font-family: DejaVu Sans, sans-serif; /* ships with DomPDF and has accents */
            font-size: 9pt;
            color: #1F2937;
        }

        .header { border-bottom: 2px solid #003A70; padding-bottom: 8px; margin-bottom: 14px; }
        .brand { font-size: 15pt; font-weight: bold; color: #003A70; }
        .brand small { font-size: 8pt; font-weight: normal; color: #6B7280; }
        h1 { font-size: 13pt; margin: 10px 0 2px; color: #003A70; }
        .meta { font-size: 8pt; color: #6B7280; }

        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th {
            background-color: #003A70;
            color: #FFFFFF;
            text-align: left;
            padding: 6px 7px;
            font-size: 8.5pt;
        }
        td { padding: 5px 7px; border-bottom: 1px solid #E5E7EB; font-size: 8.5pt; }
        tr:nth-child(even) td { background-color: #F5F7FA; }

        .summary { margin-top: 10px; font-size: 9pt; }
        .summary span { display: inline-block; margin-right: 16px; }
        .summary strong { color: #003A70; }

        .empty { margin-top: 24px; text-align: center; color: #6B7280; font-style: italic; }

        .footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            font-size: 7.5pt;
            color: #9CA3AF;
            border-top: 1px solid #E5E7EB;
            padding-top: 4px;
        }
        .footer .right { float: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            JESA — DocFlow
            <small>{{ __('common.app_tagline') }}</small>
        </div>
        <h1>{{ $report->title() }}</h1>
        <div class="meta">
            {{ __('reports.export.generated_at', ['date' => now()->translatedFormat('d F Y à H:i')]) }}
            &nbsp;·&nbsp;
            {{ __('reports.export.generated_by', ['name' => $generatedBy]) }}
        </div>
        <div class="meta">
            {{ __('reports.export.filters_applied') }} :
            {{ $filterSummary ?: __('reports.export.no_filters') }}
        </div>
    </div>

    @if ($report->summary !== [])
        <div class="summary">
            @foreach ($report->summary as $caption => $value)
                <span>{{ $caption }} : <strong>{{ $value }}</strong></span>
            @endforeach
        </div>
    @endif

    @if ($report->isEmpty())
        <p class="empty">{{ __('reports.empty.description') }}</p>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($report->headings as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($report->rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        {{ __('common.prototype_notice') }}
        <span class="right">{{ trans_choice('reports.rows', count($report->rows), ['count' => count($report->rows)]) }}</span>
    </div>
</body>
</html>
