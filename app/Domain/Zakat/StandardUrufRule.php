<?php

namespace App\Domain\Zakat;

use Brick\Math\BigDecimal;

/**
 * Default uruf table: 85g for kept gold, 200g for worn gold
 * (common Malaysian practice). v1.0 ships only this rule.
 */
final class StandardUrufRule implements UrufRule
{
    public function urufFor(GoldCategory $category): BigDecimal
    {
        return match ($category) {
            GoldCategory::KEPT => BigDecimal::of('85'),
            GoldCategory::WORN => BigDecimal::of('200'),
        };
    }

    public function displayLabel(): string
    {
        return 'Standard (85g kept / 200g worn)';
    }
}
