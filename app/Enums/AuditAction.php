<?php

namespace App\Enums;

enum AuditAction: string
{
    case Login = 'login';
    case Logout = 'logout';
    case ProductSaved = 'product';
    case PriceSaved = 'price';
    case StockAdjusted = 'stock_adjustment';
    case OrderCancelled = 'order_cancelled';
    case DealerApproved = 'dealer_approved';
    case DealerRejected = 'dealer_rejected';
    case LedgerPosted = 'ledger';
    case CollectionRecorded = 'collection';
    case UserSaved = 'user';
    case PermissionChanged = 'permission';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Giriş',
            self::Logout => 'Çıkış',
            self::ProductSaved => 'Ürün',
            self::PriceSaved => 'Fiyat',
            self::StockAdjusted => 'Stok düzeltme',
            self::OrderCancelled => 'Sipariş iptali',
            self::DealerApproved => 'Bayi onayı',
            self::DealerRejected => 'Bayi ret',
            self::LedgerPosted => 'Cari',
            self::CollectionRecorded => 'Tahsilat',
            self::UserSaved => 'Kullanıcı',
            self::PermissionChanged => 'Yetki',
        };
    }
}
