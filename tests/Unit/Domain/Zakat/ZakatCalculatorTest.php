<?php

use App\Domain\Zakat\GoldCategory;
use App\Domain\Zakat\StandardUrufRule;
use App\Domain\Zakat\ZakatCalculator;
use App\Domain\Zakat\ZakatInput;
use Brick\Math\BigDecimal;

it('matches the Android reference golden values', function () {
    $input = new ZakatInput(
        weightGrams: BigDecimal::of('120'),
        category: GoldCategory::KEPT,
        valuePerGram: BigDecimal::of('350'),
        currencyCode: 'MYR',
    );
    $result = ZakatCalculator::calculate($input, new StandardUrufRule);

    expect($result->weightMinusUruf->toScale(2)->toString())->toBe('35.00')
        ->and($result->payableValue->toScale(2)->toString())->toBe('12250.00')
        ->and($result->zakatDue->toString())->toBe('306.25');
});

it('rounds the final zakat to 2 decimals with HALF_UP at the last step', function () {
    $input = new ZakatInput(
        weightGrams: BigDecimal::of('145'),
        category: GoldCategory::KEPT,
        valuePerGram: BigDecimal::of('0.01'),
        currencyCode: 'MYR',
    );
    $result = ZakatCalculator::calculate($input, new StandardUrufRule);

    expect($result->zakatDue->getScale())->toBe(2);
    expect($result->zakatDue->toString())->toBe('0.02');
});

it('returns zero payable below and at the uruf boundary', function () {
    $below = new ZakatInput(
        weightGrams: BigDecimal::of('50'),
        category: GoldCategory::KEPT,
        valuePerGram: BigDecimal::of('350'),
        currencyCode: 'MYR',
    );
    $result = ZakatCalculator::calculate($below, new StandardUrufRule);

    expect($result->belowUruf)->toBeTrue()
        ->and($result->payableValue->toString())->toBe('0')
        ->and($result->zakatDue->toString())->toBe('0.00');

    $atBoundary = new ZakatInput(
        weightGrams: BigDecimal::of('85'),
        category: GoldCategory::KEPT,
        valuePerGram: BigDecimal::of('350'),
        currencyCode: 'MYR',
    );
    $atResult = ZakatCalculator::calculate($atBoundary, new StandardUrufRule);

    expect($atResult->belowUruf)->toBeTrue()
        ->and($atResult->zakatDue->toString())->toBe('0.00');
});

it('keeps precision on decimal weights and values until display', function () {
    $input = new ZakatInput(
        weightGrams: BigDecimal::of('85.5'),
        category: GoldCategory::KEPT,
        valuePerGram: BigDecimal::of('350.25'),
        currencyCode: 'MYR',
    );
    $result = ZakatCalculator::calculate($input, new StandardUrufRule);

    expect($result->weightMinusUruf->toScale(2)->toString())->toBe('0.50')
        ->and($result->payableValue->toString())->toBe('175.125');

    expect($result->zakatDue->getScale())->toBe(2);
});

expect(ZakatCalculator::ZAKAT_RATE)->toBe('0.025');
