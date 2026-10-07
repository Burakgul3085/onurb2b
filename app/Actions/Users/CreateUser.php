<?php

namespace App\Actions\Users;

use App\Actions\Audit\RecordAudit;
use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUser
{
    /**
     * @param  array{name: string, email: string, password: string, roles: list<string>, is_active?: bool, dealer_id?: int|null}  $data
     */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => $data['is_active'] ?? true,
                'dealer_id' => $data['dealer_id'] ?? null,
                'email_verified_at' => now(),
            ]);

            $user->syncRoles($data['roles']);

            app(RecordAudit::class)->write(AuditAction::UserSaved, $user, $user->email, [], [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'dealer_id' => $user->dealer_id,
            ]);
            app(RecordAudit::class)->write(AuditAction::PermissionChanged, $user, $user->email, [], [
                'roles' => array_values($data['roles']),
            ]);

            return $user;
        });
    }
}
