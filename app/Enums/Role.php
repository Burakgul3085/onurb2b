<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Warehouse = 'warehouse';
    case Delivery = 'delivery';
    case FinanceOps = 'finance_ops';
    case Dealer = 'dealer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Sistem yöneticisi',
            self::Admin => 'Yönetici',
            self::Warehouse => 'Depo',
            self::Delivery => 'Teslimat',
            self::FinanceOps => 'Cari',
            self::Dealer => 'Bayi',
        };
    }
}
