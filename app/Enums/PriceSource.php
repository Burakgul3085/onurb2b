<?php

namespace App\Enums;

enum PriceSource: string
{
    case Dealer = 'dealer';
    case List = 'list';
    case Product = 'product';

    public function label(): string
    {
        return match ($this) {
            self::Dealer => 'Size özel fiyat',
            self::List => 'Fiyat listesi',
            self::Product => 'Genel satış fiyatı',
        };
    }
}
