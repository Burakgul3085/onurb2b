<?php

namespace App\Http\Controllers;

use App\Actions\Orders\ApproveOrder;
use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\MarkOrderPreparing;
use App\Actions\Orders\PlaceOrder;
use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Http\Requests\Orders\ApproveOrderRequest;
use App\Http\Requests\Orders\CancelOrderRequest;
use App\Http\Requests\Orders\PlaceOrderRequest;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Policies\LedgerEntryPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $status = (string) $request->string('status');
        $actor = $request->user();

        $orders = Order::query()
            ->with('dealer')
            ->when($actor->dealer_id !== null, fn ($query) => $query->where('dealer_id', $actor->dealer_id))
            ->when($status !== '' && OrderStatus::tryFrom($status) !== null, fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'status' => $status,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function store(PlaceOrderRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        try {
            $order = $placeOrder->execute($request->user(), $request->validated());
        } catch (OrderException $exception) {
            return back()->withInput()->withErrors(['order' => $exception->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('status', __('Order sent.'));
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($request->user()?->can('view', $order), 404);

        $order->load(['lines', 'dealer', 'warehouse', 'user', 'approver', 'canceller', 'deliveries.driver', 'deliveries.document', 'ledgerEntries']);
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $warehouse = $this->selectedWarehouse($request, $warehouses, $order);

        return view('orders.show', [
            'order' => $order,
            'warehouses' => $warehouses,
            'warehouse' => $warehouse,
            'availability' => $warehouse === null ? [] : $this->availability($warehouse, $order),
            'canApprove' => $request->user()->can('approve', $order),
            'canCancel' => $request->user()->can('cancel', $order),
            'canReturn' => app(LedgerEntryPolicy::class)->returnGoods($request->user(), $order),
            'canScheduleDelivery' => $request->user()->can('create', Delivery::class) && in_array($order->status, [
                OrderStatus::Approved,
                OrderStatus::Preparing,
                OrderStatus::PartiallyDelivered,
                OrderStatus::DeliveryFailed,
            ], true),
        ]);
    }

    public function approve(ApproveOrderRequest $request, Order $order, ApproveOrder $approveOrder): RedirectResponse
    {
        $warehouse = Warehouse::query()->findOrFail($request->integer('warehouse_id'));
        $approved = [];

        foreach ($request->input('approved', []) as $lineId => $pieces) {
            $approved[(int) $lineId] = (int) $pieces;
        }

        try {
            $approveOrder->execute($order, $warehouse, $approved, $request->user());
        } catch (OrderException $exception) {
            return back()->withInput()->withErrors(['approved' => $exception->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('status', __('Order approved.'));
    }

    public function prepare(Request $request, Order $order, MarkOrderPreparing $markOrderPreparing): RedirectResponse
    {
        abort_unless($request->user()?->can('approve', $order), 403);

        try {
            $markOrderPreparing->execute($order);
        } catch (OrderException $exception) {
            return back()->withErrors(['order' => $exception->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('status', __('Order is being prepared.'));
    }

    public function cancel(CancelOrderRequest $request, Order $order, CancelOrder $cancelOrder): RedirectResponse
    {
        try {
            $cancelOrder->execute($order, $request->user(), $request->string('reason')->toString());
        } catch (OrderException $exception) {
            return back()->withInput()->withErrors(['reason' => $exception->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('status', __('Order cancelled.'));
    }

    /**
     * @param  Collection<int, Warehouse>  $warehouses
     */
    private function selectedWarehouse(Request $request, $warehouses, Order $order): ?Warehouse
    {
        if ($order->warehouse_id !== null) {
            return $order->warehouse;
        }

        $requested = $request->integer('warehouse_id');

        if ($requested > 0) {
            return $warehouses->firstWhere('id', $requested) ?? $warehouses->first();
        }

        return $warehouses->firstWhere('name', 'Merkez Depo') ?? $warehouses->first();
    }

    /**
     * @return array<int, int>
     */
    private function availability(Warehouse $warehouse, Order $order): array
    {
        $levels = StockLevel::query()
            ->where('warehouse_id', $warehouse->id)
            ->whereIn('product_id', $order->lines->pluck('product_id'))
            ->get()
            ->keyBy('product_id');

        $available = [];

        foreach ($order->lines as $line) {
            $available[$line->product_id] = $levels->get($line->product_id)?->available() ?? 0;
        }

        return $available;
    }
}
