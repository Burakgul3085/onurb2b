<?php

namespace App\Http\Controllers;

use App\Actions\Products\CreateProduct;
use App\Actions\Products\UpdateProduct;
use App\Enums\Permission;
use App\Enums\VatRate;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Services\Pricing\PriceResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request, PriceResolver $prices): View
    {
        $this->authorize('viewAny', Product::class);

        $search = trim((string) $request->string('search'));
        $like = '%'.addcslashes($search, '%_\\').'%';
        $actor = $request->user();

        $products = Product::query()
            ->with(['brand', 'category.parent', 'unit'])
            ->when($actor->dealer_id !== null, fn (Builder $query) => $query->where('is_active', true))
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

        $quotes = null;

        if ($actor->dealer_id !== null) {
            $actor->loadMissing('dealer.priceList');
            $quotes = $prices->quoteMany($actor->dealer, $products->getCollection(), 1);
        }

        return view('products.index', [
            'products' => $products,
            'search' => $search,
            'showsCosts' => $this->showsCosts($request),
            'quotes' => $quotes,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('products.create', $this->formData());
    }

    public function store(StoreProductRequest $request, CreateProduct $createProduct): RedirectResponse
    {
        $product = $createProduct->execute($request->validated(), $request->file('image'));

        return redirect()->route('products.show', $product)->with('status', __('Product saved.'));
    }

    public function show(Request $request, Product $product, PriceResolver $prices): View
    {
        $this->authorize('view', $product);

        $product->load(['brand', 'category.parent', 'unit', 'barcodes']);
        $actor = $request->user();
        $quote = null;

        if ($actor->dealer_id !== null) {
            $actor->loadMissing('dealer.priceList');
            $quote = $prices->quote($actor->dealer, $product, 1);
        }

        return view('products.show', [
            'product' => $product,
            'showsCosts' => $this->showsCosts($request),
            'quote' => $quote,
        ]);
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load(['category.parent', 'barcodes']);

        return view('products.edit', [
            'product' => $product,
            ...$this->formData($product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProduct $updateProduct): RedirectResponse
    {
        $updateProduct->execute($product, $request->validated(), $request->file('image'));

        return redirect()->route('products.show', $product)->with('status', __('Product saved.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Product $product = null): array
    {
        $brandId = $product?->brand_id;
        $category = $product?->category;
        $categoryId = $category?->parent_id ?? $category?->id;
        $unitId = $product?->unit_id;

        return [
            'product' => $product,
            'brands' => $this->activeOrCurrent(Brand::query(), $brandId)->orderBy('name')->get(),
            'categories' => $this->activeOrCurrent(Category::query()->whereNull('parent_id'), $categoryId)->orderBy('name')->get(),
            'subcategories' => Category::query()->with('parent')->whereNotNull('parent_id')->where('is_active', true)->orderBy('name')->get(),
            'units' => $this->activeOrCurrent(Unit::query(), $unitId)->orderBy('name')->get(),
            'vatRates' => VatRate::cases(),
            'selectedCategoryId' => $categoryId,
            'selectedSubcategoryId' => $category?->parent_id ? $category->id : null,
        ];
    }

    private function showsCosts(Request $request): bool
    {
        $actor = $request->user();

        return $actor->dealer_id === null && $actor->can(Permission::ProductsManage->value);
    }

    private function activeOrCurrent(Builder $query, ?int $currentId): Builder
    {
        return $query->where(function (Builder $query) use ($currentId) {
            $query->where('is_active', true);

            if ($currentId !== null) {
                $query->orWhere('id', $currentId);
            }
        });
    }
}
