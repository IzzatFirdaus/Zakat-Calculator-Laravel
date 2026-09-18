<?php

use App\Domain\Zakat\GoldCategory;
use App\Domain\Zakat\StandardUrufRule;
use App\Domain\Zakat\UrufRule;

it('returns 85g uruf for kept gold and 200g for worn gold', function () {
    $rule = new StandardUrufRule;

    expect($rule->urufFor(GoldCategory::KEPT)->toString())->toBe('85')
        ->and($rule->urufFor(GoldCategory::WORN)->toString())->toBe('200');
});

it('exposes a display label', function () {
    $rule = new StandardUrufRule;

    expect($rule->displayLabel())->toBeString()
        ->and($rule->displayLabel())->not->toBeEmpty();
});

expect(new StandardUrufRule)->toBeInstanceOf(UrufRule::class);
