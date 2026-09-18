<?php

namespace App\Domain\Zakat;

use Brick\Math\BigDecimal;

/**
 * Immutable result of a Zakat calculation.
 *
 * All monetary fields are exact decimals; display scaling to 2 decimal
 * places (HALF_UP) happens at serialization, never mid-formula.
 */
final class ZakatResult
{
    public function __construct(
        public readonly BigDecimal $urufGrams,
        public readonly BigDecimal $weightMinusUruf,
        public readonly bool $belowUruf,
        public readonly BigDecimal $payableValue,
        public readonly BigDecimal $zakatDue,
        public readonly BigDecimal $zakatRate,
        public readonly string $currencyCode,
    ) {}
}
