<?php

namespace App\Enums;

enum LedgerType: string
{
    case Sale = 'sale';
    case Collection = 'collection';
    case Return = 'return';
    case Reversal = 'reversal';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Satış',
            self::Collection => 'Tahsilat',
            self::Return => 'İade',
            self::Reversal => 'Ters kayıt',
        };
    }
}
