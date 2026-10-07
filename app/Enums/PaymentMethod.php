<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case Eft = 'eft';
    case Pos = 'pos';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Nakit',
            self::Transfer => 'Havale',
            self::Eft => 'EFT',
            self::Pos => 'POS',
        };
    }
}
