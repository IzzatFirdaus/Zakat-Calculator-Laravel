<?php

namespace App\Domain\Zakat;

use Brick\Math\BigDecimal;

/**
 * Immutable input to the Zakat calculation engine.
 *
 * Enforces positivity (strictly greater than zero) as a defensive domain
 * invariant. Upper-bound checks (weight <= 1,000,000g, value <= 10,000,000)
 * live at the request boundary. Purity boundary: no framework imports in
 * this namespace.
 */
final class ZakatInput
{
    public function __construct(
        public readonly BigDecimal $weightGrams,
        public readonly GoldCategory $category,
        public readonly BigDecimal $valuePerGram,
        public readonly string $currencyCode,
    ) {
        if ($weightGrams->isLessThanOrEqualTo(BigDecimal::zero())) {
            throw new \InvalidArgumentException('Weight must be greater than zero.');
        }

        if ($valuePerGram->isLessThanOrEqualTo(BigDecimal::zero())) {
            throw new \InvalidArgumentException('Value per gram must be greater than zero.');
        }
    }
}
