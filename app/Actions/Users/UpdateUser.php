<?php

namespace App\Actions\Users;

use App\Actions\Audit\RecordAudit;
use App\Enums\AuditAction;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateUser
{
    /**
     * @param  array{name: string, email: string, password?: string|null, roles: list<string>, is_active?: bool}  $data
     */
    public function execute(User $actor, User $user, array $data): User
    {
        return DB::transaction(function () use ($actor, $user, $data) {
            $roles = $data['roles'];
            $before = [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ];
            $beforeRoles = $user->getRoleNames()->sort()->values()->all();

            if (
                $user->hasRole(Role::SuperAdmin->value)
                && ! in_array(Role::SuperAdmin->value, $roles, true)
                && User::role(Role::SuperAdmin->value)->count() <= 1
            ) {
                throw ValidationException::withMessages([
                    'roles' => __('The last system administrator role cannot be removed.'),
                ]);
            }

            $attributes = [
                'name' => $data['name'],
                'email' => $data['email'],
            ];

            if (filled($data['password'] ?? null)) {
                $attributes['password'] = $data['password'];
            }

            if (array_key_exists('is_active', $data)) {
                $attributes['is_active'] = $this->activeState($actor, $user, (bool) $data['is_active']);
            }

            $user->update($attributes);
            $user->syncRoles($roles);
            $user->refresh();

            app(RecordAudit::class)->write(AuditAction::UserSaved, $user, $user->email, $before, [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'password_changed' => filled($data['password'] ?? null),
            ], $actor);

            $user->unsetRelation('roles');
            $afterRoles = $user->getRoleNames()->sort()->values()->all();

            if ($beforeRoles !== $afterRoles) {
                app(RecordAudit::class)->write(AuditAction::PermissionChanged, $user, $user->email, [
                    'roles' => $beforeRoles,
                ], [
                    'roles' => $afterRoles,
                ], $actor);
            }

            return $user;
        });
    }

    private function activeState(User $actor, User $user, bool $isActive): bool
    {
        if ($isActive === $user->is_active) {
            return $user->is_active;
        }

        if (! $actor->can(Permission::UsersDeactivate->value)) {
            throw ValidationException::withMessages([
                'is_active' => __('You are not allowed to change this account status.'),
            ]);
        }

        if ($actor->is($user) && ! $isActive) {
            throw ValidationException::withMessages([
                'is_active' => __('You cannot deactivate your own account.'),
            ]);
        }

        if (
            ! $isActive
            && $user->hasRole(Role::SuperAdmin->value)
            && User::role(Role::SuperAdmin->value)->where('is_active', true)->count() <= 1
        ) {
            throw ValidationException::withMessages([
                'is_active' => __('The last active system administrator cannot be deactivated.'),
            ]);
        }

        return $isActive;
    }
}
