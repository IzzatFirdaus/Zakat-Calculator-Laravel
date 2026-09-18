<?php

namespace App\Domain\Zakat;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Pure calculation engine for Zakat on gold holdings.
 *
 * Formula (single source of truth, do not duplicate):
 *   weightMinusUruf = weightGrams - uruf(category)
 *   belowUruf       = weightMinusUruf <= 0
 *   payableValue    = belowUruf ? 0 : weightMinusUruf * valuePerGram
 *   zakatDue        = payableValue * ZAKAT_RATE, scaled 2, HALF_UP
 *
 * All arithmetic uses brick/math BigDecimal; float is forbidden
 * for currency and weight math in this namespace.
 */
final class ZakatCalculator
{
    public const ZAKAT_RATE = '0.025';

    public const SCALE = 2;

    public static function calculate(ZakatInput $input, UrufRule $rule): ZakatResult
    {
        $uruf = $rule->urufFor($input->category);
        $weightMinusUruf = $input->weightGrams->minus($uruf);
        $belowUruf = $weightMinusUruf->isLessThanOrEqualTo(BigDecimal::zero());

        $payableValue = $belowUruf
            ? BigDecimal::zero()
            : $weightMinusUruf->multipliedBy($input->valuePerGram);

        $zakatDue = $payableValue
            ->multipliedBy(BigDecimal::of(self::ZAKAT_RATE))
            ->toScale(self::SCALE, RoundingMode::HalfUp);

        return new ZakatResult(
            urufGrams: $uruf,
            weightMinusUruf: $weightMinusUruf,
            belowUruf: $belowUruf,
            payableValue: $payableValue,
            zakatDue: $zakatDue,
            zakatRate: BigDecimal::of(self::ZAKAT_RATE),
            currencyCode: $input->currencyCode,
        );
    }
}
