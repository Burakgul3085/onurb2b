<?php

namespace App\Support\Finance;

use App\Models\Order;
use App\Models\OrderLine;
use App\Support\Money\Discount;
use App\Support\Money\Money;
use App\Support\Money\Vat;

final class DeliveredValue
{
    /**
     * @param  array<int, int>  $piecesByOrderLineId
     */
    public static function payable(Order $order, array $piecesByOrderLineId): string
    {
        $order->loadMissing('lines');
        $gross = '0.00';

        foreach ($order->lines as $line) {
            $gross = Money::add($gross, self::lineGross($line, $piecesByOrderLineId[$line->id] ?? 0));
        }

        return Discount::apply($gross, $order->document_discount_percent);
    }

    public static function lineGross(OrderLine $line, int $pieces): string
    {
        if ($pieces < 1) {
            return '0.00';
        }

        $discounted = Discount::apply($line->unit_price, $line->discount_percent);
        $base = Money::round(bcdiv(bcmul($discounted, (string) $pieces, 6), (string) $line->pieces_per_unit, 6));

        return Vat::split($base, $line->vat_rate, $line->prices_include_vat)['gross'];
    }
}
