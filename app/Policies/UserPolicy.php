<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::UsersView->value);
    }

    public function view(User $actor, User $subject): bool
    {
        return $actor->can(Permission::UsersView->value);
    }

    public function create(User $actor): bool
    {
        return $actor->can(Permission::UsersCreate->value);
    }

    public function update(User $actor, User $subject): bool
    {
        if (! $actor->can(Permission::UsersUpdate->value)) {
            return false;
        }

        if ($subject->hasRole(Role::SuperAdmin->value) && ! $actor->hasRole(Role::SuperAdmin->value)) {
            return false;
        }

        return true;
    }
}
