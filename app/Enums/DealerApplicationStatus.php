<?php

namespace App\Enums;

enum DealerApplicationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Beklemede',
            self::Approved => 'Onaylandı',
            self::Rejected => 'Reddedildi',
        };
    }
}
