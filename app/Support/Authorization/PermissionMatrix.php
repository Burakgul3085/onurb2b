<?php

namespace App\Support\Authorization;

use App\Enums\Permission;
use App\Enums\Role;

class PermissionMatrix
{
    /**
     * @return list<Permission>
     */
    public static function for(Role $role): array
    {
        return match ($role) {
            Role::SuperAdmin => Permission::cases(),
            Role::Admin => array_values(array_filter(
                Permission::cases(),
                fn (Permission $permission) => $permission !== Permission::SettingsManage,
            )),
            Role::Warehouse => [
                Permission::ProductsView,
                Permission::StockView,
                Permission::StockAdjust,
                Permission::WarehousesView,
                Permission::OrdersView,
            ],
            Role::Delivery => [
                Permission::DeliveriesView,
                Permission::DeliveriesManage,
                Permission::OrdersView,
            ],
            Role::FinanceOps => [
                Permission::FinanceView,
                Permission::FinanceCollect,
                Permission::DealersView,
                Permission::OrdersView,
                Permission::ReportsView,
            ],
            Role::Dealer => [
                Permission::ProductsView,
                Permission::OrdersView,
                Permission::OrdersCreate,
                Permission::FinanceView,
                Permission::MessagesView,
                Permission::MessagesSend,
            ],
        };
    }
}
