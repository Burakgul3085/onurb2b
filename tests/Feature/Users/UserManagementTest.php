<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use App\Support\Authorization\PermissionMatrix;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Role as RoleModel;

test('guests cannot open user management', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

test('warehouse staff cannot list users', function () {
    $this->actingAs(actingAsRole(Role::Warehouse))
        ->get(route('users.index'))
        ->assertForbidden();
});

test('admin can create a user with an operational role', function () {
    $admin = actingAsRole(Role::Admin);

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Depo Kullanıcısı',
        'email' => 'depo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [Role::Warehouse->value],
        'is_active' => '1',
    ])->assertRedirect(route('users.index'));

    $created = User::query()->where('email', 'depo@example.com')->first();

    expect($created)->not->toBeNull()
        ->and($created->hasRole(Role::Warehouse->value))->toBeTrue()
        ->and($created->is_active)->toBeTrue();
});

test('admin cannot assign the system administrator role', function () {
    $admin = actingAsRole(Role::Admin);

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Yetkisiz',
        'email' => 'yetkisiz@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [Role::SuperAdmin->value],
        'is_active' => '1',
    ])->assertSessionHasErrors('roles');

    expect(User::query()->where('email', 'yetkisiz@example.com')->exists())->toBeFalse();
});

test('admin cannot edit a system administrator', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $super = User::factory()->create();
    $super->assignRole(Role::SuperAdmin->value);

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->get(route('users.edit', $super))
        ->assertForbidden();
});

test('system administrator can assign another system administrator', function () {
    $super = actingAsRole(Role::SuperAdmin);

    $this->actingAs($super)->post(route('users.store'), [
        'name' => 'İkinci Yönetici',
        'email' => 'ikinci@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [Role::SuperAdmin->value],
        'is_active' => '1',
    ])->assertRedirect(route('users.index'));

    expect(User::query()->where('email', 'ikinci@example.com')->first()?->hasRole(Role::SuperAdmin->value))->toBeTrue();
});

test('a user can hold more than one role', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole([Role::Warehouse->value, Role::Delivery->value]);

    expect($user->hasRole(Role::Warehouse->value))->toBeTrue()
        ->and($user->hasRole(Role::Delivery->value))->toBeTrue()
        ->and($user->can(Permission::StockAdjust->value))->toBeTrue()
        ->and($user->can(Permission::DeliveriesManage->value))->toBeTrue()
        ->and($user->can(Permission::UsersView->value))->toBeFalse();
});

test('inactive users cannot sign in', function () {
    $user = User::factory()->create([
        'email' => 'pasif@example.com',
        'is_active' => false,
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('an inactive session is signed out', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('the last system administrator role cannot be removed', function () {
    $super = actingAsRole(Role::SuperAdmin);

    $this->actingAs($super)->patch(route('users.update', $super), [
        'name' => $super->name,
        'email' => $super->email,
        'roles' => [Role::Admin->value],
    ])->assertSessionHasErrors('roles');

    expect($super->fresh()->hasRole(Role::SuperAdmin->value))->toBeTrue();
});

test('an administrator can deactivate another user', function () {
    $admin = actingAsRole(Role::Admin);
    $warehouse = User::factory()->create();
    $warehouse->assignRole(Role::Warehouse->value);

    $this->actingAs($admin)->patch(route('users.update', $warehouse), [
        'name' => $warehouse->name,
        'email' => $warehouse->email,
        'roles' => [Role::Warehouse->value],
        'is_active' => '0',
    ])->assertRedirect(route('users.index'));

    expect($warehouse->fresh()->is_active)->toBeFalse()
        ->and($warehouse->fresh()->hasRole(Role::Warehouse->value))->toBeTrue();
});

test('a system administrator cannot deactivate their own account', function () {
    $super = actingAsRole(Role::SuperAdmin);

    $this->actingAs($super)->patch(route('users.update', $super), [
        'name' => $super->name,
        'email' => $super->email,
        'roles' => [Role::SuperAdmin->value],
        'is_active' => '0',
    ])->assertSessionHasErrors('is_active');

    expect($super->fresh()->is_active)->toBeTrue();
});

test('seeded role permissions match the matrix', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $admin = RoleModel::findByName(Role::Admin->value);
    $expected = collect(PermissionMatrix::for(Role::Admin))
        ->map(fn ($permission) => $permission->value)
        ->sort()
        ->values()
        ->all();

    expect($admin->permissions->pluck('name')->sort()->values()->all())->toEqual($expected);
});
