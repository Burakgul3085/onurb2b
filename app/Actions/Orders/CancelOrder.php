<?php

namespace App\Actions\Orders;

use App\Actions\Audit\RecordAudit;
use App\Actions\Stock\RecordStockMovement;
use App\Enums\AuditAction;
use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelOrder
{
    public function __construct(private RecordStockMovement $stock) {}

    public function execute(Order $order, User $actor, string $reason): Order
    {
        return DB::transaction(function () use ($order, $actor, $reason) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $order->load(['lines.product', 'warehouse']);

            if (! $order->status->canCancel()) {
                throw new OrderException(__('This order can no longer be cancelled.'));
            }

            $previous = $order->status->value;

            if (in_array($order->status, [OrderStatus::Approved, OrderStatus::Preparing], true)) {
                $warehouse = $order->warehouse;

                if ($warehouse === null) {
                    throw new OrderException(__('Choose an active warehouse.'));
                }

                foreach ($order->lines as $line) {
                    if ($line->approved_pieces > 0) {
                        $this->stock->release($warehouse, $line->product, $line->approved_pieces);
                    }
                }
            }

            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancellation_reason' => trim($reason),
            ]);

            app(RecordAudit::class)->write(
                AuditAction::OrderCancelled,
                $order,
                $order->number,
                ['status' => $previous],
                ['status' => OrderStatus::Cancelled->value, 'reason' => trim($reason)],
                $actor,
            );

            return $order->refresh();
        });
    }
}
