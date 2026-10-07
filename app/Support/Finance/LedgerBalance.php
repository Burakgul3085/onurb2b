<?php

namespace App\Support\Finance;

use App\Models\LedgerEntry;
use App\Support\Money\Money;

final class LedgerBalance
{
    public function forDealer(int $dealerId): string
    {
        $balance = '0.00';

        LedgerEntry::query()
            ->where('dealer_id', $dealerId)
            ->orderBy('id')
            ->each(function (LedgerEntry $entry) use (&$balance): void {
                $balance = Money::sub(Money::add($balance, $entry->debit), $entry->credit);
            });

        return $balance;
    }

    /**
     * @param  iterable<int, int>  $dealerIds
     * @return array<int, string>
     */
    public function forDealers(iterable $dealerIds): array
    {
        $balances = [];

        foreach ($dealerIds as $dealerId) {
            $balances[(int) $dealerId] = '0.00';
        }

        if ($balances === []) {
            return [];
        }

        LedgerEntry::query()
            ->whereIn('dealer_id', array_keys($balances))
            ->orderBy('id')
            ->each(function (LedgerEntry $entry) use (&$balances): void {
                $balances[$entry->dealer_id] = Money::sub(
                    Money::add($balances[$entry->dealer_id], $entry->debit),
                    $entry->credit,
                );
            });

        return $balances;
    }
}
