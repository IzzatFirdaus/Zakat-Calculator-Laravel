<?php

namespace App\Domain\Zakat;

/**
 * Gold holding classification driving the uruf exemption.
 *
 * KEPT: gold held as savings or investment (85g uruf).
 * WORN: gold worn as personal jewelry (200g uruf).
 */
enum GoldCategory: string
{
    case KEPT = 'kept';
    case WORN = 'worn';
}
