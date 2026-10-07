<?php

namespace App\Data\Prices;

use App\Enums\PriceSource;

readonly class PriceQuote
{
    public function __construct(
        public PriceSource $source,
        public string $unitPrice,
        public string $discountPercent,
        public string $discountedPrice,
        public bool $includesVat,
        public string $net,
        public string $vat,
        public string $gross,
        public string $documentDiscountPercent,
        public int $minimumQuantity,
    ) {}
}
