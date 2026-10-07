<?php

namespace App\Actions\Users;

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

            return $user;
        });
    }
}
