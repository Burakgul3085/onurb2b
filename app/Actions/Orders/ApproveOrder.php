<?php

namespace App\Actions\Orders;

use App\Actions\Mail\Notify;
use App\Actions\Stock\RecordStockMovement;
use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Exceptions\StockException;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class ApproveOrder
{
    public function __construct(private RecordStockMovement $stock) {}

    /**
     * @param  array<int, int>  $approvedPieces
     */
    public function execute(Order $order, Warehouse $warehouse, array $approvedPieces, User $actor): Order
    {
        try {
            return DB::transaction(function () use ($order, $warehouse, $approvedPieces, $actor) {
                $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                $order->load('lines.product');

                if ($order->status !== OrderStatus::Pending) {
                    throw new OrderException(__('Only a pending order can be approved.'));
                }

                if (! $warehouse->is_active) {
                    throw new OrderException(__('Choose an active warehouse.'));
                }

                $total = 0;

                foreach ($order->lines as $line) {
                    if (! array_key_exists($line->id, $approvedPieces)) {
                        throw new OrderException(__('Enter an approved quantity for every line.'));
                    }

                    $pieces = $approvedPieces[$line->id];

                    if ($pieces < 0 || $pieces > $line->requested_pieces) {
                        throw new OrderException(__('Approved quantity cannot exceed the requested quantity.'));
                    }

                    $total += $pieces;
                }

                if ($total < 1) {
                    throw new OrderException(__('Approve at least one piece or cancel the order.'));
                }

                foreach ($order->lines as $line) {
                    $pieces = $approvedPieces[$line->id];
                    $line->approved_pieces = $pieces;
                    $line->save();

                    if ($pieces > 0) {
                        $this->stock->reserve($warehouse, $line->product, $pieces);
                    }
                }

                $order->update([
                    'status' => OrderStatus::Approved,
                    'warehouse_id' => $warehouse->id,
                    'approved_at' => now(),
                    'approved_by' => $actor->id,
                ]);

                DB::afterCommit(fn () => app(Notify::class)->orderApproved($order->id));

                return $order->refresh()->load('lines');
            });
        } catch (StockException $exception) {
            throw new OrderException($exception->getMessage(), previous: $exception);
        }
    }
}
