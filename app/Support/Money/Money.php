<?php

namespace App\Support\Money;

use InvalidArgumentException;

final class Money
{
    public static function of(string|int $amount): string
    {
        $normalized = str_replace(',', '.', trim((string) $amount));

        if (! preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
            throw new InvalidArgumentException('Invalid money amount.');
        }

        return self::round($normalized);
    }

    public static function round(string $amount): string
    {
        $negative = str_starts_with($amount, '-');
        $absolute = ltrim($amount, '-');
        $rounded = bcadd($absolute, '0.005', 3);
        $rounded = bcadd($rounded, '0', 2);

        return ($negative ? '-' : '').$rounded;
    }

    public static function add(string $left, string $right): string
    {
        return self::round(bcadd(self::of($left), self::of($right), 4));
    }

    public static function sub(string $left, string $right): string
    {
        return self::round(bcsub(self::of($left), self::of($right), 4));
    }

    public static function format(string $amount): string
    {
        $normalized = self::of($amount);
        $negative = str_starts_with($normalized, '-');
        [$whole, $fraction] = explode('.', ltrim($normalized, '-'));
        $grouped = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole) ?: $whole;

        return ($negative ? '-' : '').$grouped.','.$fraction;
    }
}
