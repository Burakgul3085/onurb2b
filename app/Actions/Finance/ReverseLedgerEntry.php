<?php

namespace App\Actions\Finance;

use App\Actions\Audit\RecordAudit;
use App\Actions\Stock\RecordStockMovement;
use App\Enums\AuditAction;
use App\Enums\LedgerType;
use App\Exceptions\FinanceException;
use App\Exceptions\StockException;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReverseLedgerEntry
{
    public function __construct(private RecordStockMovement $stock) {}

    public function execute(LedgerEntry $entry, ?string $note, User $actor): LedgerEntry
    {
        try {
            return DB::transaction(function () use ($entry, $note, $actor) {
                $entry = LedgerEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();

                if ($entry->type === LedgerType::Sale) {
                    throw new FinanceException(__('A sale is corrected with a return.'));
                }

                if ($entry->type === LedgerType::Reversal || LedgerEntry::query()->where('reverses_entry_id', $entry->id)->exists()) {
                    throw new FinanceException(__('This entry is already reversed.'));
                }

                if ($entry->type === LedgerType::Return) {
                    $this->restoreReturnedStock($entry, $actor);
                }

                $text = trim((string) $note);

                $reversal = LedgerEntry::query()->create([
                    'number' => LedgerEntry::nextNumber(),
                    'dealer_id' => $entry->dealer_id,
                    'order_id' => $entry->order_id,
                    'user_id' => $actor->id,
                    'type' => LedgerType::Reversal,
                    'method' => $entry->method,
                    'debit' => $entry->credit,
                    'credit' => $entry->debit,
                    'document_date' => now()->toDateString(),
                    'note' => $text !== '' ? $text : $entry->number,
                    'reverses_entry_id' => $entry->id,
                ]);

                app(RecordAudit::class)->write(AuditAction::LedgerPosted, $reversal, $reversal->number, [
                    'reverses' => $entry->number,
                ], [
                    'type' => LedgerType::Reversal->value,
                    'debit' => (string) $reversal->debit,
                    'credit' => (string) $reversal->credit,
                ], $actor);

                return $reversal;
            });
        } catch (StockException $exception) {
            throw new FinanceException($exception->getMessage(), previous: $exception);
        }
    }

    private function restoreReturnedStock(LedgerEntry $entry, User $actor): void
    {
        $orderReturn = $entry->orderReturn()->with('lines.orderLine.product', 'warehouse', 'order')->first();

        if ($orderReturn === null || $orderReturn->warehouse === null) {
            throw new FinanceException(__('Choose an active warehouse.'));
        }

        foreach ($orderReturn->lines as $line) {
            $this->stock->sale($orderReturn->warehouse, $line->orderLine->product, $line->pieces, $orderReturn->order->number, $actor);
            $line->orderLine->decrement('returned_pieces', $line->pieces);
        }
    }
}
