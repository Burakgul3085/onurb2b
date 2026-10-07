<?php

namespace App\Services\Cart;

use App\Data\Cart\CartLine;
use App\Data\Cart\CartSummary;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Dealer;
use App\Models\Product;
use App\Services\Pricing\PriceResolver;
use App\Support\Money\Discount;
use App\Support\Money\Money;
use App\Support\Money\Vat;

class CartCalculator
{
    public function __construct(private PriceResolver $prices) {}

    public function summarize(?Cart $cart, Dealer $dealer): CartSummary
    {
        $percent = $this->prices->documentDiscountPercent($dealer->loadMissing('priceList'));

        if ($cart === null) {
            return new CartSummary([], '0.00', '0.00', '0.00', $percent, '0.00', '0.00');
        }

        $cart->loadMissing(['items.product.unit']);
        $sellable = $cart->items->filter(fn (CartItem $item) => $item->product?->is_active);
        $pieces = [];

        foreach ($sellable as $item) {
            $pieces[$item->product_id] = $item->quantity * $item->product->unit->pieces();
        }

        $quotes = $this->prices->quoteEach(
            $dealer,
            $sellable->map(fn (CartItem $item) => $item->product),
            fn (Product $product) => $pieces[$product->id],
        );

        $lines = [];
        $net = '0.00';
        $vat = '0.00';
        $gross = '0.00';

        foreach ($cart->items as $item) {
            $product = $item->product;
            $sells = $product?->is_active === true && isset($quotes[$product->id]);

            if (! $sells) {
                $lines[] = new CartLine($item, false, 0, null, '0.00', '0.00', '0.00');

                continue;
            }

            $quote = $quotes[$product->id];
            $amount = Money::round(bcmul($quote->discountedPrice, (string) $item->quantity, 6));
            $split = Vat::split($amount, $product->vat_rate, $quote->includesVat);
            $lines[] = new CartLine(
                $item,
                true,
                $pieces[$product->id],
                $quote,
                $split['net'],
                $split['vat'],
                $split['gross'],
            );
            $net = Money::add($net, $split['net']);
            $vat = Money::add($vat, $split['vat']);
            $gross = Money::add($gross, $split['gross']);
        }

        $payable = Discount::apply($gross, $percent);

        return new CartSummary(
            $lines,
            $net,
            $vat,
            $gross,
            $percent,
            Money::sub($gross, $payable),
            $payable,
        );
    }
}
