<?php

namespace App\Http\Controllers;

use App\Actions\Prices\SavePriceList;
use App\Actions\Prices\SavePriceRecord;
use App\Http\Requests\Prices\PriceListItemRequest;
use App\Http\Requests\Prices\PriceListRequest;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PriceListController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', PriceList::class);

        return view('price-lists.index', [
            'priceLists' => PriceList::query()->withCount('dealers')->orderBy('name')->paginate(15),
            'canManage' => request()->user()?->can('managePrices') ?? false,
        ]);
    }

    public function store(PriceListRequest $request, SavePriceList $savePriceList): RedirectResponse
    {
        $priceList = $savePriceList->execute($request->validated());

        return redirect()->route('price-lists.show', $priceList)->with('status', __('Price list saved.'));
    }

    public function show(PriceList $priceList): View
    {
        $this->authorize('view', $priceList);

        $priceList->load(['items.product']);

        return view('price-lists.show', [
            'priceList' => $priceList,
            'products' => Product::query()->orderBy('name')->get(['id', 'sku', 'name']),
            'canManage' => request()->user()?->can('managePrices') ?? false,
        ]);
    }

    public function edit(PriceList $priceList): View
    {
        $this->authorize('update', $priceList);

        return view('price-lists.edit', ['priceList' => $priceList]);
    }

    public function update(PriceListRequest $request, PriceList $priceList, SavePriceList $savePriceList): RedirectResponse
    {
        $savePriceList->execute($request->validated(), $priceList);

        return redirect()->route('price-lists.show', $priceList)->with('status', __('Price list saved.'));
    }

    public function storeItem(PriceListItemRequest $request, PriceList $priceList, SavePriceRecord $savePriceRecord): RedirectResponse
    {
        $savePriceRecord->forList($priceList, $request->validated());

        return redirect()->route('price-lists.show', $priceList)->with('status', __('Price saved.'));
    }

    public function editItem(PriceList $priceList, PriceListItem $item): View
    {
        $this->authorize('managePrices');
        abort_unless($item->price_list_id === $priceList->id, 404);

        return view('price-lists.edit-item', [
            'priceList' => $priceList,
            'item' => $item,
            'products' => Product::query()->orderBy('name')->get(['id', 'sku', 'name']),
        ]);
    }

    public function updateItem(PriceListItemRequest $request, PriceList $priceList, PriceListItem $item, SavePriceRecord $savePriceRecord): RedirectResponse
    {
        abort_unless($item->price_list_id === $priceList->id, 404);
        $savePriceRecord->forList($priceList, $request->validated(), $item);

        return redirect()->route('price-lists.show', $priceList)->with('status', __('Price saved.'));
    }
}
