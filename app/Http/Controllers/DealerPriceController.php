<?php

namespace App\Http\Controllers;

use App\Actions\Prices\SavePriceRecord;
use App\Http\Requests\Prices\DealerPriceRequest;
use App\Models\Dealer;
use App\Models\DealerPrice;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DealerPriceController extends Controller
{
    public function index(Dealer $dealer): View
    {
        $this->authorize('viewPrices');
        $this->authorize('view', $dealer);

        return view('dealers.prices', [
            'dealer' => $dealer,
            'prices' => $dealer->prices()->with('product')->orderBy('product_id')->orderByDesc('minimum_quantity')->get(),
            'products' => Product::query()->orderBy('name')->get(['id', 'sku', 'name']),
            'canManage' => request()->user()?->can('managePrices') ?? false,
        ]);
    }

    public function store(DealerPriceRequest $request, Dealer $dealer, SavePriceRecord $savePriceRecord): RedirectResponse
    {
        $this->authorize('view', $dealer);
        $savePriceRecord->forDealer($dealer->id, $request->validated());

        return redirect()->route('dealers.prices.index', $dealer)->with('status', __('Price saved.'));
    }

    public function edit(Dealer $dealer, DealerPrice $dealerPrice): View
    {
        $this->authorize('managePrices');
        $this->authorize('view', $dealer);
        abort_unless($dealerPrice->dealer_id === $dealer->id, 404);

        return view('dealers.edit-price', [
            'dealer' => $dealer,
            'price' => $dealerPrice,
            'products' => Product::query()->orderBy('name')->get(['id', 'sku', 'name']),
        ]);
    }

    public function update(DealerPriceRequest $request, Dealer $dealer, DealerPrice $dealerPrice, SavePriceRecord $savePriceRecord): RedirectResponse
    {
        $this->authorize('view', $dealer);
        abort_unless($dealerPrice->dealer_id === $dealer->id, 404);
        $savePriceRecord->forDealer($dealer->id, $request->validated(), $dealerPrice);

        return redirect()->route('dealers.prices.index', $dealer)->with('status', __('Price saved.'));
    }
}
