<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use NumberFormatter;

/**
 * Presentation-layer money formatting: grouped thousands, always two decimals.
 * Locale is pinned to 'en' so output never follows the request locale.
 */
final class MoneyFormatter
{
    public static function format(BigDecimal $amount): string
    {
        static $formatter = null;

        $formatter ??= new NumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);

        // ponytail: toFloat() is exact for this app's input ceilings (max payable
        // < 1e13 at scale 2 < float64 integer ceiling 9e15). Upgrade path: decimal
        // string injection if ceilings ever grow.
        return $formatter->format($amount->toScale(2, RoundingMode::HalfUp)->toFloat());
    }
}
