<?php

namespace App\Actions\Finance;

use App\Enums\LedgerType;
use App\Models\Delivery;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Support\Finance\DeliveredValue;
use Illuminate\Support\Facades\DB;

class PostDeliverySale
{
    /**
     * @param  array<int, int>  $deliveredPiecesByOrderLine
     */
    public function execute(Delivery $delivery, array $deliveredPiecesByOrderLine, User $actor): ?LedgerEntry
    {
        return DB::transaction(function () use ($delivery, $deliveredPiecesByOrderLine, $actor) {
            $delivery->loadMissing('order.dealer', 'order.lines');
            $payable = DeliveredValue::payable($delivery->order, $deliveredPiecesByOrderLine);

            if (bccomp($payable, '0.00', 2) !== 1) {
                return null;
            }

            if (LedgerEntry::query()->where('delivery_id', $delivery->id)->exists()) {
                return LedgerEntry::query()->where('delivery_id', $delivery->id)->first();
            }

            $postedOn = now();
            $days = (int) $delivery->order->dealer->payment_term_days;

            return LedgerEntry::query()->create([
                'number' => LedgerEntry::nextNumber(),
                'dealer_id' => $delivery->order->dealer_id,
                'order_id' => $delivery->order_id,
                'delivery_id' => $delivery->id,
                'user_id' => $actor->id,
                'type' => LedgerType::Sale,
                'debit' => $payable,
                'credit' => '0.00',
                'document_date' => $postedOn->toDateString(),
                'due_on' => $postedOn->copy()->addDays($days)->toDateString(),
                'note' => $delivery->order->number,
            ]);
        });
    }
}
