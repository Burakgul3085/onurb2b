<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class CompanySettingPolicy
{
    public function update(User $actor): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::SettingsManage->value);
    }
}
