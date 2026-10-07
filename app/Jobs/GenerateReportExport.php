<?php

namespace App\Jobs;

use App\Enums\ReportExportStatus;
use App\Enums\ReportFormat;
use App\Exports\TabularReportExport;
use App\Models\CompanySetting;
use App\Models\ReportExport;
use App\Support\Reports\ReportQuery;
use App\Support\Reports\Reports;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class GenerateReportExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $exportId) {}

    public function handle(Reports $reports): void
    {
        $export = ReportExport::query()->find($this->exportId);

        if ($export === null || $export->status !== ReportExportStatus::Queued) {
            return;
        }

        try {
            $query = ReportQuery::fromArray($export->filters);
            $result = $reports->build($query);
            $path = 'reports/'.$export->id.'.'.$export->format->value;

            if ($export->format === ReportFormat::Xlsx) {
                Excel::store(new TabularReportExport($result), $path, 'local');
            } else {
                Storage::disk('local')->put($path, Pdf::loadView('reports.pdf', [
                    'result' => $result,
                    'title' => $query->type?->label() ?? 'Rapor',
                    'period' => $query->period->label(),
                    'company' => CompanySetting::current()->legal_name,
                ])->setPaper('a4', 'landscape')->output());
            }

            $export->update([
                'status' => ReportExportStatus::Ready,
                'path' => $path,
                'error' => null,
            ]);
        } catch (Throwable $exception) {
            $export->update([
                'status' => ReportExportStatus::Failed,
                'error' => Str::limit($exception->getMessage(), 1000),
            ]);
        }
    }
}
