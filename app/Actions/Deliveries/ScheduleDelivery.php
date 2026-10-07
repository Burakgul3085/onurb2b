<?php

namespace App\Actions\Deliveries;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Exceptions\DeliveryException;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ScheduleDelivery
{
    /**
     * @param  array{user_id: int, sequence: int, scheduled_on: string, scheduled_time?: string|null}  $data
     */
    public function execute(Order $order, array $data): Delivery
    {
        return DB::transaction(function () use ($order, $data) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $order->load('lines');

            if (! in_array($order->status, [
                OrderStatus::Approved,
                OrderStatus::Preparing,
                OrderStatus::PartiallyDelivered,
                OrderStatus::DeliveryFailed,
            ], true)) {
                throw new DeliveryException(__('This order cannot be loaded for delivery.'));
            }

            if ($order->warehouse_id === null) {
                throw new DeliveryException(__('Choose an active warehouse.'));
            }

            $remaining = (int) $order->lines->sum('approved_pieces') - (int) $order->lines->sum('delivered_pieces');

            if ($remaining < 1) {
                throw new DeliveryException(__('There is nothing left to deliver.'));
            }

            if (Delivery::query()->where('order_id', $order->id)->whereIn('status', [
                DeliveryStatus::Preparing,
                DeliveryStatus::OutForDelivery,
            ])->exists()) {
                throw new DeliveryException(__('This order already has an open delivery.'));
            }

            $driver = User::query()->find($data['user_id']);

            if ($driver === null || ! $driver->is_active || $driver->dealer_id !== null || ! $driver->hasRole(Role::Delivery->value)) {
                throw new DeliveryException(__('Choose an active delivery person.'));
            }

            $taken = Delivery::query()
                ->where('user_id', $driver->id)
                ->whereDate('scheduled_on', $data['scheduled_on'])
                ->where('sequence', $data['sequence'])
                ->exists();

            if ($taken) {
                throw new DeliveryException(__('This delivery sequence is already used.'));
            }

            $time = $data['scheduled_time'] ?? null;

            return Delivery::query()->create([
                'order_id' => $order->id,
                'dealer_id' => $order->dealer_id,
                'user_id' => $driver->id,
                'sequence' => $data['sequence'],
                'scheduled_on' => $data['scheduled_on'],
                'scheduled_time' => is_string($time) && $time !== '' ? $time : null,
                'status' => DeliveryStatus::Preparing,
                'province' => $order->province,
                'district' => $order->district,
                'delivery_address' => $order->delivery_address,
            ]);
        });
    }
}
