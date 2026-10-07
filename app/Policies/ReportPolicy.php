<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::ReportsView->value);
    }
}
