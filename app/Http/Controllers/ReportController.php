<?php

namespace App\Http\Controllers;

use App\Enums\DealerApplicationStatus;
use App\Enums\DeliveryStatus;
use App\Enums\LedgerType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ReportExportStatus;
use App\Enums\ReportFormat;
use App\Enums\ReportPeriod;
use App\Enums\ReportType;
use App\Enums\StockMovementType;
use App\Exports\TabularReportExport;
use App\Http\Requests\Reports\ShowReportRequest;
use App\Jobs\GenerateReportExport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Dealer;
use App\Models\Product;
use App\Models\ReportExport;
use App\Policies\ReportPolicy;
use App\Support\Reports\ReportQuery;
use App\Support\Reports\Reports;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ShowReportRequest $request, Reports $reports): View|RedirectResponse|Response|BinaryFileResponse
    {
        $query = ReportQuery::fromArray($request->validated());
        $format = ReportFormat::tryFrom($request->string('format')->toString());
        $tooLarge = $query->type !== null && $reports->count($query) > (int) config('reports.queue_threshold');

        if ($tooLarge && $format !== null) {
            $export = ReportExport::query()->create([
                'user_id' => $request->user()->id,
                'type' => $query->type,
                'format' => $format,
                'filters' => $query->parameters(),
                'status' => ReportExportStatus::Queued,
            ]);
            GenerateReportExport::dispatch($export->id);

            return redirect()
                ->route('reports.index', $query->parameters())
                ->with('status', __('Report queued.'));
        }

        if ($query->type !== null && $format !== null) {
            $result = $reports->build($query);
            $name = $query->type->value.'.'.$format->value;

            if ($format === ReportFormat::Xlsx) {
                return Excel::download(new TabularReportExport($result), $name);
            }

            return Pdf::loadView('reports.pdf', [
                'result' => $result,
                'title' => $query->type->label(),
                'period' => $query->period->label(),
                'company' => CompanySetting::current()->legal_name,
            ])->setPaper('a4', 'landscape')->stream($name);
        }

        $result = $query->type === null || $tooLarge ? null : $reports->build($query);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $rows = $result === null ? [] : array_slice($result->rows, ($page - 1) * 25, 25);
        $paginator = $result === null ? null : new LengthAwarePaginator($rows, count($result->rows), 25, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('reports.index', [
            'query' => $query,
            'result' => $result,
            'rows' => $rows,
            'paginator' => $paginator,
            'types' => ReportType::cases(),
            'periods' => ReportPeriod::cases(),
            'statuses' => $this->statuses(),
            'dealers' => Dealer::query()->orderBy('company_name')->get(['id', 'company_name']),
            'products' => Product::query()->orderBy('name')->get(['id', 'sku', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'exports' => ReportExport::query()->where('user_id', $request->user()->id)->latest('id')->limit(10)->get(),
            'tooLarge' => $tooLarge,
        ]);
    }

    public function download(Request $request, ReportExport $export): StreamedResponse
    {
        abort_unless(app(ReportPolicy::class)->viewAny($request->user()), 403);
        abort_unless((int) $export->user_id === (int) $request->user()->id, 404);
        abort_unless($export->status === ReportExportStatus::Ready && is_string($export->path), 404);
        abort_unless(Storage::disk('local')->exists($export->path), 404);

        return Storage::disk('local')->download($export->path, $export->type->value.'.'.$export->format->value);
    }

    /**
     * @return array<string, string>
     */
    private function statuses(): array
    {
        $options = ['stock:critical' => 'Stok: Kritik'];

        foreach (OrderStatus::cases() as $case) {
            $options['order:'.$case->value] = 'Sipariş: '.$case->label();
        }

        foreach (DeliveryStatus::cases() as $case) {
            $options['delivery:'.$case->value] = 'Teslimat: '.$case->label();
        }

        foreach (StockMovementType::cases() as $case) {
            $options['movement:'.$case->value] = 'Hareket: '.$case->label();
        }

        foreach (DealerApplicationStatus::cases() as $case) {
            $options['dealer:'.$case->value] = 'Bayi: '.$case->label();
        }

        foreach (LedgerType::cases() as $case) {
            $options['ledger:'.$case->value] = 'Cari: '.$case->label();
        }

        foreach (PaymentMethod::cases() as $case) {
            $options['collection:'.$case->value] = 'Tahsilat: '.$case->label();
        }

        return $options;
    }
}
