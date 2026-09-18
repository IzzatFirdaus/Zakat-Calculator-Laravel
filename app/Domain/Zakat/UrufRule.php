<?php

namespace App\Domain\Zakat;

use Brick\Math\BigDecimal;

/**
 * Strategy for determining the uruf exemption (grams of gold) per category.
 *
 * Mirrors the Android NisabRule seam: future school-of-thought presets
 * implement this interface without touching ZakatCalculator.
 */
interface UrufRule
{
    public function urufFor(GoldCategory $category): BigDecimal;

    public function displayLabel(): string;
}
