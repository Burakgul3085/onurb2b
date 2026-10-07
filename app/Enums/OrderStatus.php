<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Preparing = 'preparing';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case PartiallyDelivered = 'partially_delivered';
    case Cancelled = 'cancelled';
    case DeliveryFailed = 'delivery_failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Taslak',
            self::Pending => 'Bekliyor',
            self::Approved => 'Onaylandı',
            self::Preparing => 'Hazırlanıyor',
            self::OutForDelivery => 'Teslimatta',
            self::Delivered => 'Teslim edildi',
            self::PartiallyDelivered => 'Kısmen teslim',
            self::Cancelled => 'İptal',
            self::DeliveryFailed => 'Teslim başarısız',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Approved, self::Preparing, self::Delivered => 'on',
            self::Cancelled, self::DeliveryFailed => 'off',
            default => 'wait',
        };
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Pending, self::Approved, self::Preparing], true);
    }
}
