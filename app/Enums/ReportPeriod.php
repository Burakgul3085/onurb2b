<?php

namespace App\Enums;

enum ReportPeriod: string
{
    case All = 'all';
    case Today = 'today';
    case Last7 = 'last_7';
    case Last30 = 'last_30';
    case ThisMonth = 'this_month';
    case LastMonth = 'last_month';
    case ThisYear = 'this_year';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Tüm tarihler',
            self::Today => 'Bugün',
            self::Last7 => 'Son 7 gün',
            self::Last30 => 'Son 30 gün',
            self::ThisMonth => 'Bu ay',
            self::LastMonth => 'Geçen ay',
            self::ThisYear => 'Bu yıl',
            self::Custom => 'Özel tarih',
        };
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    public function bounds(?string $from, ?string $to): array
    {
        $today = now()->startOfDay();

        return match ($this) {
            self::All => [null, null],
            self::Today => [$today->toDateString(), $today->toDateString()],
            self::Last7 => [$today->copy()->subDays(6)->toDateString(), $today->toDateString()],
            self::Last30 => [$today->copy()->subDays(29)->toDateString(), $today->toDateString()],
            self::ThisMonth => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()],
            self::LastMonth => [
                $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            self::ThisYear => [$today->copy()->startOfYear()->toDateString(), $today->toDateString()],
            self::Custom => [$from, $to],
        };
    }
}
