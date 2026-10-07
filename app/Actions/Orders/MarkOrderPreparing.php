<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class MarkOrderPreparing
{
    public function execute(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== OrderStatus::Approved) {
                throw new OrderException(__('Only an approved order can be prepared.'));
            }

            $order->update(['status' => OrderStatus::Preparing]);

            return $order->refresh();
        });
    }
}
