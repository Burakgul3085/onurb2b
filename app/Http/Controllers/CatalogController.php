<?php

namespace App\Http\Controllers;

use App\Actions\Cart\AddCartItem;
use App\Actions\Cart\FindSellableProduct;
use App\Exceptions\CartException;
use App\Http\Requests\Cart\QuickOrderRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockLevel;
use App\Services\Pricing\PriceResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request, PriceResolver $prices): View
    {
        abort_unless($request->user()?->can('shop'), 403);

        $search = trim((string) $request->string('search'));
        $brandId = $request->integer('brand_id');
        $categoryId = $request->integer('category_id');
        $like = '%'.addcslashes($search, '%_\\').'%';
        $dealer = $request->user()->dealer()->with('priceList')->first();

        $products = Product::query()
            ->with(['brand', 'category.parent', 'unit'])
            ->where('is_active', true)
            ->when($brandId > 0, fn (Builder $query) => $query->where('brand_id', $brandId))
            ->when($categoryId > 0, function (Builder $query) use ($categoryId) {
                $query->where(function (Builder $query) use ($categoryId) {
                    $query->where('category_id', $categoryId)
                        ->orWhereHas('category', fn (Builder $query) => $query->where('parent_id', $categoryId));
                });
            })
            ->when($search !== '', function (Builder $query) use ($like) {
                $query->where(function (Builder $query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like)
                        ->orWhereHas('barcodes', fn (Builder $query) => $query->where('barcode', 'like', $like));
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $quotes = $prices->quoteEach(
            $dealer,
            $products->getCollection(),
            fn (Product $product) => $product->unit->pieces(),
        );

        return view('catalog.index', [
            'products' => $products,
            'quotes' => $quotes,
            'availability' => $this->availability($products->getCollection()->pluck('id')->all()),
            'search' => $search,
            'brandId' => $brandId,
            'categoryId' => $categoryId,
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::query()->with('parent')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function quickOrder(QuickOrderRequest $request, FindSellableProduct $find, AddCartItem $add): RedirectResponse
    {
        try {
            $product = $find->byCode($request->string('code')->toString());
            $add->execute($request->user(), $product, $request->integer('quantity'));
        } catch (CartException $exception) {
            return back()->withInput()->withErrors(['code' => $exception->getMessage()]);
        }

        return back()->with('status', __('Added to cart.'));
    }

    /**
     * @param  list<int>  $productIds
     * @return array<int, bool>
     */
    private function availability(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $totals = StockLevel::query()
            ->select('product_id')
            ->selectRaw('SUM(physical_stock - reserved_stock) as available')
            ->whereIn('product_id', $productIds)
            ->whereHas('warehouse', fn (Builder $query) => $query->where('is_active', true))
            ->groupBy('product_id')
            ->pluck('available', 'product_id');

        $flags = [];

        foreach ($productIds as $id) {
            $flags[$id] = (int) ($totals[$id] ?? 0) > 0;
        }

        return $flags;
    }
}
