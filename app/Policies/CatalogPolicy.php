<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class CatalogPolicy
{
    public function manage(User $actor): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::ProductsManage->value);
    }
}
