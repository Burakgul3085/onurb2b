<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Preparing = 'preparing';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Preparing => 'Hazırlanıyor',
            self::OutForDelivery => 'Teslimatta',
            self::Delivered => 'Teslim edildi',
            self::Failed => 'Teslim başarısız',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Delivered => 'on',
            self::Failed => 'off',
            default => 'wait',
        };
    }
}
