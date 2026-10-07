<?php

namespace App\Actions\Deliveries;

use App\Actions\Stock\RecordStockMovement;
use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Exceptions\DeliveryException;
use App\Exceptions\StockException;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DispatchDelivery
{
    public function __construct(
        private RecordStockMovement $stock,
        private SyncOrderAfterDelivery $syncOrder,
    ) {}

    /**
     * @param  array<int, int>  $shippedPieces
     */
    public function execute(Delivery $delivery, array $shippedPieces, User $actor): Delivery
    {
        try {
            return DB::transaction(function () use ($delivery, $shippedPieces, $actor) {
                $delivery = Delivery::query()->whereKey($delivery->id)->lockForUpdate()->firstOrFail();
                $delivery->load('order.lines.product', 'order.warehouse');
                $order = $delivery->order;

                if ($delivery->status !== DeliveryStatus::Preparing || $order->warehouse === null) {
                    throw new DeliveryException(__('Only a planned delivery can leave the warehouse.'));
                }

                $total = 0;

                foreach ($order->lines as $line) {
                    $pieces = $shippedPieces[$line->id] ?? 0;
                    $remaining = $line->approved_pieces - $line->delivered_pieces;

                    if ($pieces < 0 || $pieces > $remaining) {
                        throw new DeliveryException(__('Shipped quantity cannot exceed the approved quantity.'));
                    }

                    $total += $pieces;
                }

                if ($total < 1) {
                    throw new DeliveryException(__('Load at least one piece.'));
                }

                foreach ($order->lines as $line) {
                    $pieces = $shippedPieces[$line->id] ?? 0;

                    if ($pieces < 1) {
                        continue;
                    }

                    $delivery->lines()->create([
                        'order_line_id' => $line->id,
                        'shipped_pieces' => $pieces,
                        'delivered_pieces' => 0,
                    ]);
                    $this->stock->release($order->warehouse, $line->product, $pieces);
                    $this->stock->sale($order->warehouse, $line->product, $pieces, $order->number, $actor);
                }

                $delivery->update([
                    'status' => DeliveryStatus::OutForDelivery,
                    'departed_at' => now(),
                ]);
                $order->update(['status' => OrderStatus::OutForDelivery]);

                return $delivery->refresh()->load('lines');
            });
        } catch (StockException $exception) {
            throw new DeliveryException($exception->getMessage(), previous: $exception);
        }
    }
}
