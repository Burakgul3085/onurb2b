<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Delivery;
use App\Models\User;

class DeliveryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::DeliveriesView->value);
    }

    public function view(User $actor, Delivery $delivery): bool
    {
        if (! $this->viewAny($actor)) {
            return false;
        }

        return $this->seesAll($actor) || $delivery->user_id === $actor->id;
    }

    public function create(User $actor): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::DeliveriesManage->value);
    }

    public function update(User $actor, Delivery $delivery): bool
    {
        if (! $this->create($actor)) {
            return false;
        }

        return $this->seesAll($actor) || $delivery->user_id === $actor->id;
    }

    public function seesAll(User $actor): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::OrdersApprove->value);
    }
}
