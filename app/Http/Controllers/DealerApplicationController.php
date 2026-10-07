<?php

namespace App\Http\Controllers;

use App\Actions\Dealers\CreateDealer;
use App\Enums\District;
use App\Http\Requests\Dealers\StoreDealerApplicationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DealerApplicationController extends Controller
{
    public function create(): View
    {
        return view('dealers.apply', [
            'districts' => District::cases(),
        ]);
    }

    public function store(StoreDealerApplicationRequest $request, CreateDealer $createDealer): RedirectResponse
    {
        $createDealer->execute($request->validated());

        return redirect()
            ->route('dealers.apply')
            ->with('status', __('Dealer application received.'));
    }
}
