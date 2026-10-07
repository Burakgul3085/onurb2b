<?php

namespace App\Actions\Finance;

use App\Enums\LedgerType;
use App\Enums\PaymentMethod;
use App\Exceptions\FinanceException;
use App\Models\Dealer;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Support\Money\Money;
use Illuminate\Support\Facades\DB;

class RecordCollection
{
    public function execute(Dealer $dealer, string $amount, PaymentMethod $method, string $documentDate, ?string $note, User $actor): LedgerEntry
    {
        $amount = Money::of($amount);

        if (bccomp($amount, '0.00', 2) !== 1) {
            throw new FinanceException(__('Enter an amount greater than zero.'));
        }

        return DB::transaction(fn () => LedgerEntry::query()->create([
            'number' => LedgerEntry::nextNumber(),
            'dealer_id' => $dealer->id,
            'user_id' => $actor->id,
            'type' => LedgerType::Collection,
            'method' => $method,
            'debit' => '0.00',
            'credit' => $amount,
            'document_date' => $documentDate,
            'note' => $this->note($note),
        ]));
    }

    private function note(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : $note;
    }
}
