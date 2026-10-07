<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class StockPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::StockView->value);
    }

    public function adjust(User $actor): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::StockAdjust->value);
    }
}
