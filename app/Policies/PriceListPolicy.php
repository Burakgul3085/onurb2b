<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\PriceList;
use App\Models\User;

class PriceListPolicy
{
    public function viewAny(User $actor): bool
    {
        return $this->staff($actor) && $actor->can(Permission::PricesView->value);
    }

    public function view(User $actor, PriceList $priceList): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $this->staff($actor) && $actor->can(Permission::PricesManage->value);
    }

    public function update(User $actor, PriceList $priceList): bool
    {
        return $this->create($actor);
    }

    private function staff(User $actor): bool
    {
        return $actor->dealer_id === null;
    }
}
