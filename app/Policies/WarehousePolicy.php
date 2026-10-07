<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(User $actor): bool
    {
        return $this->staff($actor) && (
            $actor->can(Permission::WarehousesView->value) || $actor->can(Permission::WarehousesManage->value)
        );
    }

    public function view(User $actor, Warehouse $warehouse): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $this->staff($actor) && $actor->can(Permission::WarehousesManage->value);
    }

    public function update(User $actor, Warehouse $warehouse): bool
    {
        return $this->create($actor);
    }

    private function staff(User $actor): bool
    {
        return $actor->dealer_id === null;
    }
}
