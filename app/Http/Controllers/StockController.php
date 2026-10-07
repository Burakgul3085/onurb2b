<?php

namespace App\Http\Controllers;

use App\Actions\Stock\RecordStockMovement;
use App\Enums\StockMovementType;
use App\Exceptions\StockException;
use App\Http\Requests\Stock\StoreStockMovementRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewStock');

        $warehouse = $this->selectedWarehouse($request);
        $search = trim((string) $request->string('search'));
        $alert = (string) $request->string('alert');
        $like = '%'.addcslashes($search, '%_\\').'%';

        $products = Product::query()
            ->leftJoin('stock_levels as levels', function ($join) use ($warehouse) {
                $join->on('levels.product_id', '=', 'products.id')
                    ->where('levels.warehouse_id', '=', $warehouse?->id ?? 0);
            })
            ->select([
                'products.*',
                DB::raw('COALESCE(levels.physical_stock, 0) as physical_stock'),
                DB::raw('COALESCE(levels.reserved_stock, 0) as reserved_stock'),
            ])
            ->where(function (Builder $query) {
                $query->where('products.is_active', true)
                    ->orWhere('levels.physical_stock', '>', 0)
                    ->orWhere('levels.reserved_stock', '>', 0);
            })
            ->when($search !== '', function (Builder $query) use ($like) {
                $query->where(function (Builder $query) use ($like) {
                    $query->where('products.name', 'like', $like)
                        ->orWhere('products.sku', 'like', $like);
                });
            })
            ->when($alert === 'critical', function (Builder $query) {
                $query->where('products.critical_stock', '>', 0)
                    ->whereRaw('COALESCE(levels.physical_stock, 0) - COALESCE(levels.reserved_stock, 0) <= products.critical_stock');
            })
            ->when($alert === 'low', function (Builder $query) {
                $query->where('products.minimum_stock', '>', 0)
                    ->whereRaw('COALESCE(levels.physical_stock, 0) - COALESCE(levels.reserved_stock, 0) <= products.minimum_stock');
            })
            ->orderBy('products.name')
            ->paginate(15)
            ->withQueryString();

        return view('stock.index', [
            'products' => $products,
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(),
            'warehouse' => $warehouse,
            'search' => $search,
            'alert' => $alert,
            'canAdjust' => $request->user()?->can('adjustStock') ?? false,
        ]);
    }

    public function movements(Request $request): View
    {
        $this->authorize('viewStock');

        $warehouseId = $request->integer('warehouse_id');
        $productId = $request->integer('product_id');
        $type = (string) $request->string('type');

        $movements = StockMovement::query()
            ->with(['warehouse', 'product', 'user', 'counterpartWarehouse'])
            ->when($warehouseId > 0, fn (Builder $query) => $query->where('warehouse_id', $warehouseId))
            ->when($productId > 0, fn (Builder $query) => $query->where('product_id', $productId))
            ->when(StockMovementType::tryFrom($type) !== null, fn (Builder $query) => $query->where('type', $type))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('stock.movements', [
            'movements' => $movements,
            'warehouses' => Warehouse::query()->orderBy('name')->get(),
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'sku']),
            'types' => StockMovementType::cases(),
            'warehouseId' => $warehouseId,
            'productId' => $productId,
            'type' => $type,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('adjustStock');

        return view('stock.create', [
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'sku']),
            'types' => array_values(array_filter(StockMovementType::cases(), fn (StockMovementType $type) => $type->isManual())),
            'selectedWarehouse' => $request->integer('warehouse_id'),
            'selectedProduct' => $request->integer('product_id'),
        ]);
    }

    public function store(StoreStockMovementRequest $request, RecordStockMovement $recordStockMovement): RedirectResponse
    {
        $warehouse = Warehouse::query()->findOrFail($request->integer('warehouse_id'));
        $product = Product::query()->findOrFail($request->integer('product_id'));
        $type = StockMovementType::from((string) $request->input('type'));
        $quantity = (int) $request->input('quantity');
        $note = $request->input('note');
        $actor = $request->user();

        try {
            if ($type === StockMovementType::Transfer) {
                $destination = Warehouse::query()->findOrFail($request->integer('destination_warehouse_id'));
                $recordStockMovement->transfer($warehouse, $destination, $product, $quantity, $note, $actor);
            } elseif ($type === StockMovementType::Count) {
                $recordStockMovement->count($warehouse, $product, $quantity, $note, $actor);
            } elseif ($type === StockMovementType::Adjustment) {
                $signed = $request->input('direction') === 'out' ? -$quantity : $quantity;
                $recordStockMovement->adjust($warehouse, $product, $signed, $note, $actor);
            } elseif ($type === StockMovementType::Damage) {
                $recordStockMovement->damage($warehouse, $product, $quantity, $note, $actor);
            } else {
                $recordStockMovement->purchase($warehouse, $product, $quantity, $note, $actor);
            }
        } catch (StockException $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return redirect()
            ->route('stock.index', ['warehouse_id' => $warehouse->id])
            ->with('status', __('Stock movement saved.'));
    }

    private function selectedWarehouse(Request $request): ?Warehouse
    {
        $requested = $request->integer('warehouse_id');

        if ($requested > 0) {
            $warehouse = Warehouse::query()->where('is_active', true)->find($requested);

            if ($warehouse !== null) {
                return $warehouse;
            }
        }

        return Warehouse::query()->where('is_active', true)->where('name', 'Merkez Depo')->first()
            ?? Warehouse::query()->where('is_active', true)->orderBy('id')->first();
    }
}
