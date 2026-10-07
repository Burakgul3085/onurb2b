<?php

namespace App\Data\Cart;

use App\Data\Prices\PriceQuote;
use App\Models\CartItem;

readonly class CartLine
{
    public function __construct(
        public CartItem $item,
        public bool $sellable,
        public int $pieces,
        public ?PriceQuote $quote,
        public string $net,
        public string $vat,
        public string $gross,
    ) {}
}
