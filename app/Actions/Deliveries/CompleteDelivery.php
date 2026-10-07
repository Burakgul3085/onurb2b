<?php

namespace App\Actions\Deliveries;

use App\Actions\Documents\IssueDeliveryDocument;
use App\Actions\Finance\PostDeliverySale;
use App\Actions\Stock\RecordStockMovement;
use App\Enums\DeliveryStatus;
use App\Exceptions\DeliveryException;
use App\Exceptions\FinanceException;
use App\Exceptions\StockException;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CompleteDelivery
{
    public function __construct(
        private RecordStockMovement $stock,
        private SyncOrderAfterDelivery $syncOrder,
        private PostDeliverySale $sales,
        private IssueDeliveryDocument $documents,
    ) {}

    /**
     * @param  array<int, int>  $deliveredPieces
     * @param  array{recipient_name: string, note?: string|null}  $data
     */
    public function execute(Delivery $delivery, array $deliveredPieces, array $data, User $actor, ?UploadedFile $proof = null): Delivery
    {
        try {
            return DB::transaction(function () use ($delivery, $deliveredPieces, $data, $actor, $proof) {
                $delivery = Delivery::query()->whereKey($delivery->id)->lockForUpdate()->firstOrFail();
                $delivery->load('lines.orderLine.product', 'order.warehouse', 'order.dealer', 'order.lines');

                if ($delivery->status !== DeliveryStatus::OutForDelivery || $delivery->order->warehouse === null) {
                    throw new DeliveryException(__('Only a delivery that has left the warehouse can be completed.'));
                }

                foreach ($delivery->lines as $line) {
                    if (! array_key_exists($line->id, $deliveredPieces)) {
                        throw new DeliveryException(__('Enter a delivered quantity for every line.'));
                    }

                    $pieces = $deliveredPieces[$line->id];

                    if ($pieces < 0 || $pieces > $line->shipped_pieces) {
                        throw new DeliveryException(__('Delivered quantity cannot exceed the shipped quantity.'));
                    }
                }

                $warehouse = $delivery->order->warehouse;

                foreach ($delivery->lines as $line) {
                    $pieces = $deliveredPieces[$line->id];
                    $returned = $line->shipped_pieces - $pieces;
                    $line->delivered_pieces = $pieces;
                    $line->save();
                    $line->orderLine->increment('delivered_pieces', $pieces);

                    if ($returned > 0) {
                        $this->stock->receiveReturn($warehouse, $line->orderLine->product, $returned, $delivery->order->number, $actor);
                        $this->stock->reserve($warehouse, $line->orderLine->product, $returned);
                    }
                }

                $delivery->update([
                    'status' => DeliveryStatus::Delivered,
                    'recipient_name' => trim($data['recipient_name']),
                    'note' => $this->note($data['note'] ?? null),
                    'proof_path' => $proof?->store('deliveries', 'public'),
                    'finished_at' => now(),
                ]);
                $this->syncOrder->execute($delivery->order);
                $deliveredByLine = [];

                foreach ($delivery->lines as $line) {
                    $deliveredByLine[$line->order_line_id] = $deliveredPieces[$line->id];
                }

                $this->sales->execute($delivery, $deliveredByLine, $actor);
                $delivery->refresh();
                $this->documents->execute($delivery);

                return $delivery;
            });
        } catch (StockException|FinanceException $exception) {
            throw new DeliveryException($exception->getMessage(), previous: $exception);
        }
    }

    private function note(mixed $note): ?string
    {
        $note = is_string($note) ? trim($note) : '';

        return $note === '' ? null : $note;
    }
}
