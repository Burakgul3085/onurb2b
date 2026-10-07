<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Support\Authorization\PermissionMatrix;

test('system administrator receives every permission', function () {
    expect(PermissionMatrix::for(Role::SuperAdmin))->toEqual(Permission::cases());
});

test('manager cannot manage system settings', function () {
    expect(PermissionMatrix::for(Role::Admin))->not->toContain(Permission::SettingsManage)
        ->and(PermissionMatrix::for(Role::Admin))->toContain(Permission::UsersView);
});

test('dealer cannot manage users', function () {
    $permissions = PermissionMatrix::for(Role::Dealer);

    expect($permissions)->toContain(Permission::OrdersCreate)
        ->and($permissions)->not->toContain(Permission::UsersView)
        ->and($permissions)->not->toContain(Permission::OrdersApprove);
});

test('warehouse can adjust stock without user administration', function () {
    $permissions = PermissionMatrix::for(Role::Warehouse);

    expect($permissions)->toContain(Permission::StockAdjust)
        ->and($permissions)->not->toContain(Permission::UsersView);
});
