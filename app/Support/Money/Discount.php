<?php

namespace App\Support\Money;

use InvalidArgumentException;

final class Discount
{
    public static function of(string|int $percent): string
    {
        $normalized = Money::of($percent);

        if (bccomp($normalized, '0', 2) < 0 || bccomp($normalized, '100', 2) > 0) {
            throw new InvalidArgumentException('Invalid discount percent.');
        }

        return $normalized;
    }

    public static function apply(string $amount, string|int $percent): string
    {
        $rate = bcsub('100', self::of($percent), 2);

        return Money::round(bcdiv(bcmul(Money::of($amount), $rate, 6), '100', 6));
    }
}
