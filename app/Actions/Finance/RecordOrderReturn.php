<?php

namespace App\Actions\Finance;

use App\Actions\Audit\RecordAudit;
use App\Actions\Stock\RecordStockMovement;
use App\Enums\AuditAction;
use App\Enums\LedgerType;
use App\Exceptions\FinanceException;
use App\Exceptions\StockException;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use App\Support\Finance\DeliveredValue;
use Illuminate\Support\Facades\DB;

class RecordOrderReturn
{
    public function __construct(private RecordStockMovement $stock) {}

    /**
     * @param  array<int, int>  $piecesByOrderLine
     */
    public function execute(Order $order, array $piecesByOrderLine, string $documentDate, ?string $note, User $actor): OrderReturn
    {
        try {
            return DB::transaction(function () use ($order, $piecesByOrderLine, $documentDate, $note, $actor) {
                $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                $order->load('lines.product', 'warehouse', 'dealer');

                if ($order->warehouse === null) {
                    throw new FinanceException(__('Choose an active warehouse.'));
                }

                $accepted = [];

                foreach ($order->lines as $line) {
                    $pieces = $piecesByOrderLine[$line->id] ?? 0;
                    $returnable = $line->delivered_pieces - $line->returned_pieces;

                    if ($pieces < 0 || $pieces > $returnable) {
                        throw new FinanceException(__('Return quantity cannot exceed the delivered quantity.'));
                    }

                    if ($pieces > 0) {
                        $accepted[$line->id] = $pieces;
                    }
                }

                if ($accepted === []) {
                    throw new FinanceException(__('There is nothing to return.'));
                }

                $payable = DeliveredValue::payable($order, $accepted);

                if (bccomp($payable, '0.00', 2) !== 1) {
                    throw new FinanceException(__('Enter an amount greater than zero.'));
                }

                $entry = LedgerEntry::query()->create([
                    'number' => LedgerEntry::nextNumber(),
                    'dealer_id' => $order->dealer_id,
                    'order_id' => $order->id,
                    'user_id' => $actor->id,
                    'type' => LedgerType::Return,
                    'debit' => '0.00',
                    'credit' => $payable,
                    'document_date' => $documentDate,
                    'note' => $this->note($note) ?? $order->number,
                ]);

                app(RecordAudit::class)->write(AuditAction::LedgerPosted, $entry, $entry->number, [], [
                    'type' => LedgerType::Return->value,
                    'credit' => (string) $entry->credit,
                    'dealer_id' => $entry->dealer_id,
                ], $actor);

                $orderReturn = OrderReturn::query()->create([
                    'order_id' => $order->id,
                    'dealer_id' => $order->dealer_id,
                    'warehouse_id' => $order->warehouse_id,
                    'user_id' => $actor->id,
                    'ledger_entry_id' => $entry->id,
                    'note' => $this->note($note),
                ]);

                foreach ($order->lines as $line) {
                    $pieces = $accepted[$line->id] ?? 0;

                    if ($pieces < 1) {
                        continue;
                    }

                    $orderReturn->lines()->create([
                        'order_line_id' => $line->id,
                        'pieces' => $pieces,
                    ]);
                    $this->stock->receiveReturn($order->warehouse, $line->product, $pieces, $order->number, $actor);
                    $line->increment('returned_pieces', $pieces);
                }

                return $orderReturn->load('entry');
            });
        } catch (StockException $exception) {
            throw new FinanceException($exception->getMessage(), previous: $exception);
        }
    }

    private function note(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : $note;
    }
}
