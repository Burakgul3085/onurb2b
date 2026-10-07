<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Dealer;
use App\Models\User;

class DealerPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::DealersView->value);
    }

    public function view(User $actor, Dealer $dealer): bool
    {
        if ($actor->dealer_id !== null) {
            return $actor->dealer_id === $dealer->id;
        }

        return $actor->can(Permission::DealersView->value);
    }

    public function create(User $actor): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::DealersCreate->value);
    }

    public function update(User $actor, Dealer $dealer): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::DealersUpdate->value);
    }

    public function approve(User $actor, Dealer $dealer): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::DealersApprove->value);
    }
}
