<?php

use App\Enums\VatRate;
use App\Support\Money\Money;
use App\Support\Money\Vat;

test('vat exclusive of 100 at 20 percent totals 120', function () {
    expect(Vat::split('100', VatRate::Twenty, false))->toBe([
        'net' => '100.00',
        'vat' => '20.00',
        'gross' => '120.00',
    ]);
});

test('vat inclusive of 100 at 20 percent splits into 83.33 and 16.67', function () {
    expect(Vat::split('100', VatRate::Twenty, true))->toBe([
        'net' => '83.33',
        'vat' => '16.67',
        'gross' => '100.00',
    ]);
});

test('money normalizes a comma and groups thousands', function () {
    expect(Money::of('10,5'))->toBe('10.50')
        ->and(Money::format('1200.5'))->toBe('1.200,50');
});
