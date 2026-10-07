<?php

namespace App\Enums;

enum Permission: string
{
    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDeactivate = 'users.deactivate';

    case DealersView = 'dealers.view';
    case DealersCreate = 'dealers.create';
    case DealersUpdate = 'dealers.update';
    case DealersApprove = 'dealers.approve';

    case ProductsView = 'products.view';
    case ProductsManage = 'products.manage';

    case StockView = 'stock.view';
    case StockAdjust = 'stock.adjust';

    case WarehousesView = 'warehouses.view';
    case WarehousesManage = 'warehouses.manage';

    case PricesView = 'prices.view';
    case PricesManage = 'prices.manage';

    case OrdersView = 'orders.view';
    case OrdersCreate = 'orders.create';
    case OrdersApprove = 'orders.approve';
    case OrdersCancel = 'orders.cancel';

    case DeliveriesView = 'deliveries.view';
    case DeliveriesManage = 'deliveries.manage';

    case FinanceView = 'finance.view';
    case FinanceCollect = 'finance.collect';

    case ReportsView = 'reports.view';

    case MessagesView = 'messages.view';
    case MessagesSend = 'messages.send';

    case SettingsManage = 'settings.manage';
    case AuditView = 'audit.view';
}
