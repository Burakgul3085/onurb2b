<?php

namespace App\Data\Cart;

readonly class CartSummary
{
    /**
     * @param  list<CartLine>  $lines
     */
    public function __construct(
        public array $lines,
        public string $net,
        public string $vat,
        public string $gross,
        public string $documentDiscountPercent,
        public string $discountAmount,
        public string $payable,
    ) {}
}
