<?php

namespace App\Http\Controllers;

use App\Enums\ReportPeriod;
use App\Http\Requests\Dashboard\ShowDashboardRequest;
use App\Support\Dashboard\DashboardMetrics;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ShowDashboardRequest $request, DashboardMetrics $metrics): View
    {
        $period = ReportPeriod::tryFrom($request->string('period')->toString()) ?? ReportPeriod::ThisMonth;

        return view('dashboard', [
            'dashboard' => $metrics->for(
                $request->user(),
                $period,
                $request->input('from'),
                $request->input('to'),
            ),
            'periods' => ReportPeriod::cases(),
        ]);
    }
}
