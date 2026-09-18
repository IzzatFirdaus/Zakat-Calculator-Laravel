<?php

use App\Support\MoneyFormatter;
use Brick\Math\BigDecimal;

it('groups thousands and pins two decimals', function (string $input, string $expected) {
    expect(MoneyFormatter::format(BigDecimal::of($input)))->toBe($expected);
})->with([
    ['0', '0.00'],
    ['0.5', '0.50'],
    ['306.25', '306.25'],
    ['12250', '12,250.00'],
    ['12250.004', '12,250.00'],
    ['12250.005', '12,250.01'],
    ['-35', '-35.00'],
    ['9999999999.99', '9,999,999,999.99'],
]);
