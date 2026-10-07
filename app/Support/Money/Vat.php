<?php

namespace App\Support\Money;

use App\Enums\VatRate;

final class Vat
{
    /**
     * @return array{net: string, vat: string, gross: string}
     */
    public static function split(string $amount, VatRate $rate, bool $includesVat): array
    {
        $amount = Money::of($amount);

        if ($rate === VatRate::Zero) {
            return ['net' => $amount, 'vat' => '0.00', 'gross' => $amount];
        }

        if (! $includesVat) {
            $vat = Money::round(bcdiv(bcmul($amount, $rate->value, 6), '100', 6));

            return [
                'net' => $amount,
                'vat' => $vat,
                'gross' => Money::add($amount, $vat),
            ];
        }

        $net = Money::round(bcdiv(bcmul($amount, '100', 6), bcadd('100', $rate->value, 0), 6));

        return [
            'net' => $net,
            'vat' => Money::sub($amount, $net),
            'gross' => $amount,
        ];
    }
}
