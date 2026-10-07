<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = config('onur.super_admin.password');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException('SUPER_ADMIN_PASSWORD tanımlı değil.');
        }

        $email = (string) config('onur.super_admin.email');

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => (string) config('onur.super_admin.name'),
                'password' => $password,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        if (! $user->hasRole(Role::SuperAdmin->value)) {
            $user->assignRole(Role::SuperAdmin->value);
        }
    }
}
