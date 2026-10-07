<?php

namespace App\Actions\Deliveries;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Models\Delivery;
use App\Models\Order;

class SyncOrderAfterDelivery
{
    public function execute(Order $order): void
    {
        $order->load('lines');
        $approved = (int) $order->lines->sum('approved_pieces');
        $delivered = (int) $order->lines->sum('delivered_pieces');
        $open = Delivery::query()
            ->where('order_id', $order->id)
            ->where('status', DeliveryStatus::OutForDelivery)
            ->exists();

        if ($open) {
            $status = OrderStatus::OutForDelivery;
        } elseif ($approved > 0 && $delivered >= $approved) {
            $status = OrderStatus::Delivered;
        } elseif ($delivered > 0) {
            $status = OrderStatus::PartiallyDelivered;
        } elseif (Delivery::query()->where('order_id', $order->id)->where('status', DeliveryStatus::Failed)->exists()) {
            $status = OrderStatus::DeliveryFailed;
        } else {
            $status = OrderStatus::Preparing;
        }

        $order->update(['status' => $status]);
    }
}
