<?php

namespace App\Actions\Deliveries;

use App\Actions\Stock\RecordStockMovement;
use App\Enums\DeliveryStatus;
use App\Exceptions\DeliveryException;
use App\Exceptions\StockException;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class FailDelivery
{
    public function __construct(
        private RecordStockMovement $stock,
        private SyncOrderAfterDelivery $syncOrder,
    ) {}

    public function execute(Delivery $delivery, string $note, User $actor, ?UploadedFile $proof = null): Delivery
    {
        try {
            return DB::transaction(function () use ($delivery, $note, $actor, $proof) {
                $delivery = Delivery::query()->whereKey($delivery->id)->lockForUpdate()->firstOrFail();
                $delivery->load('lines.orderLine.product', 'order.warehouse');

                if ($delivery->status !== DeliveryStatus::OutForDelivery || $delivery->order->warehouse === null) {
                    throw new DeliveryException(__('Only a delivery that has left the warehouse can fail.'));
                }

                $warehouse = $delivery->order->warehouse;

                foreach ($delivery->lines as $line) {
                    $this->stock->receiveReturn($warehouse, $line->orderLine->product, $line->shipped_pieces, $delivery->order->number, $actor);
                    $this->stock->reserve($warehouse, $line->orderLine->product, $line->shipped_pieces);
                }

                $delivery->update([
                    'status' => DeliveryStatus::Failed,
                    'note' => trim($note),
                    'proof_path' => $proof?->store('deliveries', 'public'),
                    'finished_at' => now(),
                ]);
                $this->syncOrder->execute($delivery->order);

                return $delivery->refresh();
            });
        } catch (StockException $exception) {
            throw new DeliveryException($exception->getMessage(), previous: $exception);
        }
    }
}
