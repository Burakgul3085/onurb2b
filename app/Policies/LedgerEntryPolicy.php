<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Dealer;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\User;

class LedgerEntryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::FinanceView->value);
    }

    public function viewDealer(User $actor, Dealer $dealer): bool
    {
        if (! $this->viewAny($actor)) {
            return false;
        }

        return $actor->dealer_id === null || (int) $actor->dealer_id === (int) $dealer->id;
    }

    public function collect(User $actor): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::FinanceCollect->value);
    }

    public function returnGoods(User $actor, Order $order): bool
    {
        return $this->collect($actor) && $order->exists;
    }

    public function reverse(User $actor, LedgerEntry $entry): bool
    {
        return $this->collect($actor);
    }

    public function deniedStatus(User $actor, Dealer $dealer): int
    {
        if ($actor->dealer_id !== null && (int) $actor->dealer_id !== (int) $dealer->id) {
            return 404;
        }

        return 403;
    }
}
