<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Purchase = 'purchase';
    case Sale = 'sale';
    case Return = 'return';
    case Adjustment = 'adjustment';
    case Transfer = 'transfer';
    case Damage = 'damage';
    case Count = 'count';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Mal girişi',
            self::Sale => 'Satış',
            self::Return => 'İade',
            self::Adjustment => 'Düzeltme',
            self::Transfer => 'Transfer',
            self::Damage => 'Hasar',
            self::Count => 'Sayım',
        };
    }

    public function isManual(): bool
    {
        return match ($this) {
            self::Purchase, self::Adjustment, self::Transfer, self::Damage, self::Count => true,
            self::Sale, self::Return => false,
        };
    }

    /**
     * @return list<string>
     */
    public static function manualValues(): array
    {
        return array_values(array_map(
            fn (self $type) => $type->value,
            array_filter(self::cases(), fn (self $type) => $type->isManual()),
        ));
    }
}
