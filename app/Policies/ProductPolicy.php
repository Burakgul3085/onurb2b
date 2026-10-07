<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ProductsView->value);
    }

    public function view(User $actor, Product $product): bool
    {
        if (! $actor->can(Permission::ProductsView->value)) {
            return false;
        }

        if ($actor->dealer_id !== null) {
            return $product->is_active;
        }

        return true;
    }

    public function create(User $actor): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::ProductsManage->value);
    }

    public function update(User $actor, Product $product): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::ProductsManage->value);
    }
}
