<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use App\Support\Authorization\PermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission, 'web');
        }

        foreach (Role::cases() as $role) {
            $model = RoleModel::findOrCreate($role, 'web');
            $model->syncPermissions(PermissionMatrix::for($role));
        }
    }
}
