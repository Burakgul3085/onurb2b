<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::OrdersView->value);
    }

    public function view(User $actor, Order $order): bool
    {
        if (! $this->viewAny($actor)) {
            return false;
        }

        return $actor->dealer_id === null || $actor->dealer_id === $order->dealer_id;
    }

    public function create(User $actor): bool
    {
        return $actor->dealer_id !== null
            && $actor->can('shop')
            && $actor->can(Permission::OrdersCreate->value);
    }

    public function approve(User $actor, Order $order): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::OrdersApprove->value);
    }

    public function cancel(User $actor, Order $order): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::OrdersCancel->value);
    }
}
