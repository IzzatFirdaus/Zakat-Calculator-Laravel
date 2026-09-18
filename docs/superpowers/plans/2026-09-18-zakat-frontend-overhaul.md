# Zakat Calculator Frontend Overhaul (Variant A) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the approved spec `docs/superpowers/specs/2026-09-18-zakat-frontend-overhaul-design.md` — Variant A "Refined Civic" UI, split CSS architecture, modular TS, server-side money formatting, and the §1 F1–F10 UX/a11y fixes — without changing the domain, the calculation, or the JSON API contract.

**Architecture:** In-place progressive refactor of a Laravel 13 + Blade + vanilla-TypeScript + hand-authored-CSS app. CSS becomes a token/base/component partial chain imported by `app.css`; `calculator.ts` becomes an entry composing four modules under `resources/js/calculator/`; one new framework-facing class `App\Support\MoneyFormatter` formats money in the Blade layer only.

**Tech Stack:** PHP 8.5, Laravel 13, Pest, brick/math BigDecimal, Vite 7 (esbuild, no typecheck step), TypeScript (syntax-only), plain CSS custom properties.

**Spec:** `docs/superpowers/specs/2026-09-18-zakat-frontend-overhaul-design.md` — read it before every task; this plan argues from it.

## Global Constraints (apply to every task)

- No new runtime or dev dependencies. No Tailwind utility classes. No JS frameworks.
- `app/Domain/Zakat/**` stays pure PHP (no `Illuminate\*`); it is **not edited by this plan at all**.
- All money/weight math stays `brick\math\BigDecimal`, final scale 2, `RoundingMode::HalfUp` (project spelling — see `CalculatorController`).
- Validation/normalization lives only in `App\Http\Requests\CalculateZakatRequest`; controllers stay thin (max 3 statements/action); do not touch either.
- All user-facing strings in `lang/en/*.php` (`__('calculator.…')`), never in Blade literals or TS string literals.
- JSON API (`CalculatorController::apiCalculate`) response shape unchanged.
- Golden values must keep passing: 120 KEPT @350 → 35.00 / 12,250.00 / 306.25; 50 KEPT → below uruf; 210 WORN @350 → 10.00 / 3,500.00 / 87.50.
- WCAG AA contrast both themes; tap targets ≥ 44px; transitions ≤ 150ms; `prefers-reduced-motion` respected.
- Gates after each PHP-touching task: `php artisan test --compact <paths>` (narrow) then `vendor/bin/pest` (full), `vendor/bin/pint --dirty --format agent`, `composer analyse` → zero new errors. After each frontend-asset task: `npm run build`.
- Git: stage intent only (never `git add -A`); never commit `.env*`, `database/database.sqlite`, `testing`, `.tests`.

## File Structure

| Path | Fate | Responsibility |
|---|---|---|
| `resources/css/app.css` | Rewrite (entry) | Only `@import` lines, in order: tokens, base, header, calculator, result, about, footer |
| `resources/css/tokens.css` | Create | `:root` + `html.dark` custom properties, radii, shadows |
| `resources/css/base.css` | Create | reset, body, selection, focus-visible, skip-link, `.sr-only`, reduced-motion |
| `resources/css/header.css` | Create | `.app-header*`, `.skip`-adjacent header media query |
| `resources/css/calculator.css` | Create | `.calculator-*`, `.field*`, `.input`, `.select`, `.control*`, `.category-*`, `.badge`, `.error-summary*`, `.actions`, `.button-*` |
| `resources/css/result.css` | Create | `.calculator-result`, `.eyebrow`, `.amount*`, `.meter*`, `.result-list*`, `.status-panel*`, `.result-state*`, `.watermark`, spin keyframes |
| `resources/css/about.css` | Create | `.methodology*` |
| `resources/css/footer.css` | Create | `.app-footer` |
| `app/Support/MoneyFormatter.php` | Create | BigDecimal → grouped 2-decimal string, locale pinned `en` |
| `resources/views/calculator/index.blade.php` | Modify | unit affordances, currency prefix chip, uruf badges, `data-calculator-reset` |
| `resources/views/calculator/partials/result.blade.php` | Rewrite | formatted money, meter, dotted-leader ledger, watermark, uruf note |
| `lang/en/calculator.php` | Modify | new keys (Task 3/4 lists, exact values) |
| `resources/js/calculator.ts` | Rewrite (entry) | DOMContentLoaded wiring only |
| `resources/js/calculator/dom.ts` | Create | `cloneTemplate()` |
| `resources/js/calculator/theme.ts` | Create | theme toggle (moved verbatim) |
| `resources/js/calculator/error-renderer.ts` | Create | `ErrorRenderer` class: 422 render/clear, summary focus, stale-error dismissal |
| `resources/js/calculator/form-controller.ts` | Create | submit/fetch/swap lifecycle, viewport handoff, prefix sync, client reset |
| `tests/Unit/Support/MoneyFormatterTest.php` | Create | formatter unit coverage |
| `tests/Feature/CalculateWebTest.php` | Modify | updated assertions + new badge/meter/formatting tests |
| `ARCHITECTURE.md` | Modify (Task 6 only) | §9 amendment lines for meter/formatter/palette deltas |

---

### Task 1: CSS split with zero visual change

**Files:**
- Create: `resources/css/tokens.css`, `base.css`, `header.css`, `calculator.css`, `result.css`, `about.css`, `footer.css`
- Modify: `resources/css/app.css` (becomes import-only entry)

**Interfaces:**
- Consumes: nothing.
- Produces: same class names as today; later tasks add classes to these files. `app.css` remains the single Vite input (no layout/vite config change).

- [ ] **Step 1: Create the seven partials by moving rule blocks verbatim** from the current `resources/css/app.css`. Mapping (line numbers refer to `app.css` as of commit `3f89444`):

| Target | Move these blocks |
|---|---|
| `tokens.css` | Lines 8–35 `@theme { … }` **converted**: delete the `@theme` wrapper and emit its contents inside `:root { … }` with the identical custom-property names (`--color-background`, `--font-sans`, `--radius-control`, `--shadow-panel`, …). Then lines 37–58 (`html.dark { … }`) verbatim. Finally append new tokens used by later tasks: `--meter-height: 8px; --leader-gap: 0.5rem; --watermark-opacity: 0.06;` in `:root` |
| `base.css` | Lines 60–103 (`html`, `html.dark` color-scheme, `body`, `::selection`, `:focus-visible`, `button/input/select` font, tap-highlight) + 105–122 (`.skip-link*`) + 909–919 (`.sr-only`) + 949–962 (reduced-motion block) |
| `header.css` | Lines 124–231 (all `.app-header*`) + the `.app-header` rules inside the `@media (max-width: 39.99rem)` block (921–940) |
| `calculator.css` | Lines 248–317 (`.calculator-shell/intro/grid/form*`) + 319–394 (`.field*`, `.input`, `.select` core, `.field__error`) + 396–441 (`.error-summary*`) + 443–567 (`.category-*`, `.currency-grid`, `.select` arrow) + 569–616 (`.actions`, `.button-*`) + the `.calculator-form` box-shadow override inside the mobile media block (941–946) |
| `result.css` | Lines 618–727 (`.calculator-result` … `.method-note`) + 881–908 (`.result-state*`, shifted by the `.methodology__version` block) + `@keyframes spin` |
| `about.css` | Lines 729–879 (all `.methodology*`, **including the newer `.methodology__version` rule**) |
| `footer.css` | Lines 239–246 (`.app-footer`) |

Line numbers are approximate as of commit `b06ab25`; **locate each block by its selector — the block list, not the numbering, is the contract**. The remaining after blocks: `.sr-only` at 915–925, mobile media block at 927–953, reduced-motion at 955–968.

Do not edit any declaration values in this step. `@source` lines (3–5) and `@import 'tailwindcss';` (1) are deleted, not moved.

- [ ] **Step 2: Rewrite `resources/css/app.css` to exactly:**

```css
@import './tokens.css';
@import './base.css';
@import './header.css';
@import './calculator.css';
@import './result.css';
@import './about.css';
@import './footer.css';
```

- [ ] **Step 3: Build and verify** — Run `npm run build`. Expected: success, and the emitted CSS bundle is *smaller* than before (Tailwind scan removed). Then run `php artisan test --compact tests/Feature/SmokeTest.php tests/Feature/CalculateWebTest.php` — expected: all pass (no test asserts CSS bytes).

- [ ] **Step 4: Spot-check in browser** — with the dev server up, load `/` and `/about`, both themes, confirm identical rendering vs `git stash`-free baseline screenshot you took earlier (spacing/colors). Check the built CSS contains no `tailwind` preflight surprises (body margin rule still wins).

- [ ] **Step 5: Full gates + commit**

```bash
vendor/bin/pest
vendor/bin/pint --dirty --format agent
composer analyse
git add resources/css
git commit -m "refactor(css): split app.css into token/base/component partials, drop unused tailwind import"
```

---

### Task 2: MoneyFormatter + formatted result partial

**Files:**
- Create: `app/Support/MoneyFormatter.php`, `tests/Unit/Support/MoneyFormatterTest.php`
- Modify: `resources/views/calculator/partials/result.blade.php`, `tests/Feature/CalculateWebTest.php`

**Interfaces:**
- Consumes: `ZakatResult` fields `urufGrams`, `weightMinusUruf`, `payableValue`, `zakatDue` (all `BigDecimal`), `currencyCode` (string).
- Produces: `App\Support\MoneyFormatter::format(BigDecimal $amount): string` — grouped, always 2 decimals, HALF_UP scale 2 inside. Task 4 calls the same method.

- [ ] **Step 1: Write the failing unit test** — `tests/Unit/Support/MoneyFormatterTest.php`:

```php
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
```

- [ ] **Step 2: Run it, expect failure** — `vendor/bin/pest tests/Unit/Support/MoneyFormatterTest.php` → class not found.

- [ ] **Step 3: Implement `app/Support/MoneyFormatter.php`:**

```php
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
```

- [ ] **Step 4: Run the unit test, expect pass** — `vendor/bin/pest tests/Unit/Support/MoneyFormatterTest.php`.

- [ ] **Step 5: Update failing feature assertions first (TDD)** — in `tests/Feature/CalculateWebTest.php`:
  - Line 92: `->assertSee('12250.00')` → `->assertSee('12,250.00')`
  - Line 108: `->assertSee('12250.00')` → `->assertSee('12,250.00')`
  - Line 370: `->assertSee('12448.69 MYR')` → `->assertSee('12,448.69 MYR')`
  - Add a new test at the end of the file:

```php
it('formats upper-bound results with thousands separators', function () {
    /** @var \Tests\TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => '1000000',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertStatus(200)
        ->assertSee('999,915.00 g')
        ->assertSee('349,970,250.00 MYR')
        ->assertSee('8,749,256.25');
});
```

- [ ] **Step 6: Run web tests, expect the new/updated ones to fail** — `php artisan test --compact tests/Feature/CalculateWebTest.php`.

- [ ] **Step 7: Rewrite the money calls in `resources/views/calculator/partials/result.blade.php`** — replace each `{{ $result->X->toScale(2, RoundingMode::HalfUp) }}` with `{{ \App\Support\MoneyFormatter::format($result->X) }}` (four occurrences: `zakatDue` hero, `urufGrams`, `weightMinusUruf`, `payableValue`, `zakatDue` total row). Delete the `@use('Brick\Math\RoundingMode')` line and its import usage (no other caller remains in this file). Do not change markup structure in this task — Task 4 rewrites it.

- [ ] **Step 8: Run tests, expect pass** — `php artisan test --compact tests/Feature/CalculateWebTest.php tests/Feature/CalculateApiTest.php` (API test proves the JSON contract still emits raw strings).

- [ ] **Step 9: Full gates + commit**

```bash
vendor/bin/pest && vendor/bin/pint --dirty --format agent && composer analyse
git add app/Support/MoneyFormatter.php tests/Unit/Support/MoneyFormatterTest.php resources/views/calculator/partials/result.blade.php tests/Feature/CalculateWebTest.php
git commit -m "feat(presentation): server-side grouped money formatting via MoneyFormatter"
```

---

### Task 3: Form affordances — uruf badges, unit suffix, currency prefix

**Files:**
- Modify: `lang/en/calculator.php`, `resources/views/calculator/index.blade.php`, `resources/css/calculator.css`, `tests/Feature/CalculateWebTest.php`

**Interfaces:**
- Consumes: `StandardUrufRule::urufFor()` values (85/200) as the drift-test oracle; `old('currency', 'MYR')`.
- Produces: DOM contracts Task 5 relies on — `[data-currency-prefix]` span inside the value `.control`, and `[data-calculator-reset]` on the reset link; classes `.control`, `.control__unit`, `.control__prefix`, `.category-option__badge`.

- [ ] **Step 1: Write the failing tests** — append to `tests/Feature/CalculateWebTest.php`:

```php
it('surfaces uruf badges matching the rule thresholds', function () {
    /** @var \Tests\TestCase $this */
    $rule = new \App\Domain\Zakat\StandardUrufRule;
    $kept = $rule->urufFor(\App\Domain\Zakat\GoldCategory::KEPT)->toInt();
    $worn = $rule->urufFor(\App\Domain\Zakat\GoldCategory::WORN)->toInt();

    $this->get('/')->assertSee("uruf {$kept} g", false)->assertSee("uruf {$worn} g", false);
});

it('renders unit affordances on the numeric inputs', function () {
    /** @var \Tests\TestCase $this */
    $content = $this->get('/')->getContent();

    expect($content)->toContain('class="control__unit"')
        ->and($content)->toContain('data-currency-prefix')
        ->and($content)->toContain('MYR');
});
```

- [ ] **Step 2: Run, expect failure** — `php artisan test --compact tests/Feature/CalculateWebTest.php`.

- [ ] **Step 3: Add lang keys** to `lang/en/calculator.php` (after `wornDescription`):

```php
'categoryKeptUruf' => 'uruf 85 g',
'categoryWornUruf' => 'uruf 200 g',
'unitGramShort' => 'g',
```

- [ ] **Step 4: Edit `resources/views/calculator/index.blade.php`:**
  - Weight field: wrap the `<input>` (keep every existing attribute) in `<div class="control">` and append `<span class="control__unit" aria-hidden="true">{{ __('calculator.unitGramShort') }}</span>` inside the wrapper. `field__help`/`@error` stay outside the wrapper.
  - Value field: same `.control` wrapper, but prepend `<span class="control__prefix" aria-hidden="true" data-currency-prefix>{{ old('currency', 'MYR') }}</span>`.
  - Category options: inside each `.category-option__content`, after the title span, add `<span class="category-option__badge num">{{ $category === 'kept' ? __('calculator.categoryKeptUruf') : __('calculator.categoryWornUruf') }}</span>` — concretely: the Kept label gets `categoryKeptUruf`, the Worn label gets `categoryWornUruf`.
  - Reset link: add `data-calculator-reset` to its attribute list.

- [ ] **Step 5: Add CSS** to `resources/css/calculator.css` (end of file; ≤150ms transitions; AA contrast):

```css
.control {
    position: relative;
    display: flex;
    align-items: center;
}

.control .input {
    padding-right: 2.5rem;
    font-variant-numeric: tabular-nums;
}

.control--prefixed .input {
    padding-left: 3.5rem;
    padding-right: 0.75rem;
}

.control__unit,
.control__prefix {
    position: absolute;
    font-size: 0.875rem;
    font-weight: 650;
    color: var(--color-muted);
    pointer-events: none;
}

.control__unit { right: 0.875rem; }
.control__prefix { left: 0.875rem; }

.category-option {
    transition: border-color 120ms ease, background-color 120ms ease;
}

.category-option:has(input:checked) {
    border-color: var(--color-primary);
    background-color: var(--color-primary-soft);
}

.category-option:has(input:focus-visible) {
    outline: 3px solid var(--color-focus);
    outline-offset: 2px;
}

.category-option__title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.category-option__badge {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--color-muted);
    border: 1px solid var(--color-border-strong);
    border-radius: 0.4rem;
    padding: 0.1rem 0.4rem;
    white-space: nowrap;
}

.category-option:has(input:checked) .category-option__badge {
    color: var(--color-primary);
    border-color: currentcolor;
}

.num {
    font-variant-numeric: tabular-nums;
}
```

In the Blade, wrap the category title span and badge together in `<span class="category-option__title-row">…</span>` (title-row contains the existing `__title` span and the new `__badge` span; the `__description` span stays below it). Give the value `.control` the extra class `control--prefixed`.

- [ ] **Step 6: Run tests + build** — `php artisan test --compact tests/Feature/CalculateWebTest.php` (new ones pass; earlier ones unchanged) and `npm run build`.

- [ ] **Step 7: Full gates + commit**

```bash
vendor/bin/pest && vendor/bin/pint --dirty --format agent && composer analyse
git add lang/en/calculator.php resources/views/calculator/index.blade.php resources/css/calculator.css tests/Feature/CalculateWebTest.php
git commit -m "feat(calculator): uruf badges, unit suffix and currency prefix affordances"
```

---

### Task 4: Result panel — meter, dotted-leader ledger, watermark

**Files:**
- Modify: `resources/views/calculator/partials/result.blade.php`, `resources/css/result.css`, `resources/css/tokens.css`, `lang/en/calculator.php`, `tests/Feature/CalculateWebTest.php`

**Interfaces:**
- Consumes: `MoneyFormatter::format()` (Task 2); `ZakatResult` BigDecimals.
- Produces: DOM contracts for Task 5 — `[data-result-heading]` stays; classes `.eyebrow`, `.amount`, `.meter` with inline custom properties `--meter-width` / `--meter-tick`.

- [ ] **Step 1: Write the failing tests** — append to `tests/Feature/CalculateWebTest.php`:

```php
it('renders the zakatable meter geometry for above-uruf results', function () {
    /** @var \Tests\TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => '120', 'category' => 'kept', 'value' => '350', 'currency' => 'MYR',
    ]);

    // 35 / 120 = 29.17% zakatable; uruf tick at 85 / 120 = 70.83%
    $response->assertStatus(200)
        ->assertSee('--meter-width:29.17%', false)
        ->assertSee('--meter-tick:70.83%', false);
});

it('hides the meter for below-uruf results', function () {
    /** @var \Tests\TestCase $this */
    $content = $this->post('/calculate', [
        'weight' => '50', 'category' => 'kept', 'value' => '350', 'currency' => 'MYR',
    ])->getContent();

    expect($content)->not->toContain('class="meter"');
});

it('labels the meter for screen readers with the result figures', function () {
    /** @var \Tests\TestCase $this */
    $content = $this->post('/calculate', [
        'weight' => '120', 'category' => 'kept', 'value' => '350', 'currency' => 'MYR',
    ])->getContent();

    expect($content)->toContain('aria-label="Of 120.00 grams entered, 35.00 grams above the 85.00 gram uruf are zakatable."');
});
```

- [ ] **Step 2: Run, expect failure** — `php artisan test --compact tests/Feature/CalculateWebTest.php`.

- [ ] **Step 3: Add lang keys** to `lang/en/calculator.php`:

```php
'resultEyebrow' => 'Estimated Zakat due',
'meterLabel' => 'Of :weight grams entered, :zakatable grams above the :uruf gram uruf are zakatable.',
'meterZakatableLabel' => 'Zakatable',
'meterTotalLabel' => 'Total',
'meterUrufTick' => ':uruf g uruf',
'resultAboutLink' => 'View methodology',
```

- [ ] **Step 4: Rewrite `resources/views/calculator/partials/result.blade.php`** in full:

```blade
<h2 class="result-panel__heading" id="result-heading" data-result-heading tabindex="-1">{{ __('calculator.resultHeading') }}</h2>

@if ($result === null)
    <div class="status-panel status-panel--empty" role="status">
        <svg class="status-panel__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <path d="M12 8v4M12 16h.01"/>
        </svg>
        <span>{{ __('calculator.resultEmpty') }}</span>
    </div>
@else
    @if ($result->belowUruf)
        <div class="status-panel" role="status">
            <svg class="status-panel__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                <path d="m9 12 2 2 4-4"/>
            </svg>
            <div>
                <div>{{ __('calculator.belowUruf') }}</div>
                <p class="status-panel__detail">{{ __('calculator.belowUrufDetail') }}</p>
            </div>
        </div>
    @else
        <p class="eyebrow">{{ __('calculator.resultEyebrow') }}</p>
        <p class="amount">{{ \App\Support\MoneyFormatter::format($result->zakatDue) }}<small class="amount__currency">{{ $result->currencyCode }}</small></p>
        @php
            // Presentational geometry only: zakatable share of the entered weight.
            // The zakat formula itself lives in ZakatCalculator — do not extend this math.
            $total = $result->weightMinusUruf->plus($result->urufGrams);
            $meterWidth = $result->weightMinusUruf->multipliedBy(100)->divide($total, 2, \Brick\Math\RoundingMode::HalfUp)->toFloat();
            $meterTick = $result->urufGrams->multipliedBy(100)->divide($total, 2, \Brick\Math\RoundingMode::HalfUp)->toFloat();
        @endphp
        <div class="meter" style="--meter-width:{{ $meterWidth }}%;--meter-tick:{{ $meterTick }}%"
             role="img"
             aria-label="{{ __('calculator.meterLabel', [
                 'weight' => \App\Support\MoneyFormatter::format($total),
                 'zakatable' => \App\Support\MoneyFormatter::format($result->weightMinusUruf),
                 'uruf' => \App\Support\MoneyFormatter::format($result->urufGrams),
             ]) }}">
            <div class="meter__labels">
                <span>{{ __('calculator.meterZakatableLabel') }} {{ \App\Support\MoneyFormatter::format($result->weightMinusUruf) }} g</span>
                <span>{{ __('calculator.meterTotalLabel') }} {{ \App\Support\MoneyFormatter::format($total) }} g</span>
            </div>
            <div class="meter__track"><div class="meter__fill"></div></div>
            <div class="meter__ticks"><span class="meter__tick">{{ __('calculator.meterUrufTick', ['uruf' => \App\Support\MoneyFormatter::format($result->urufGrams)]) }}</span></div>
        </div>
    @endif

    <dl class="result-list">
        <div class="result-list__row">
            <dt class="result-list__label">{{ __('calculator.urufApplied') }}</dt>
            <span class="result-list__leader" aria-hidden="true"></span>
            <dd class="result-list__value num">{{ \App\Support\MoneyFormatter::format($result->urufGrams) }} g</dd>
        </div>
        <div class="result-list__row">
            <dt class="result-list__label">{{ __('calculator.resultWeightMinusUruf') }}</dt>
            <span class="result-list__leader" aria-hidden="true"></span>
            <dd class="result-list__value num">{{ \App\Support\MoneyFormatter::format($result->weightMinusUruf) }} g</dd>
        </div>
        <div class="result-list__row">
            <dt class="result-list__label">{{ __('calculator.resultPayableValue') }}</dt>
            <span class="result-list__leader" aria-hidden="true"></span>
            <dd class="result-list__value num">{{ \App\Support\MoneyFormatter::format($result->payableValue) }} {{ $result->currencyCode }}</dd>
        </div>
        <div class="result-list__row result-list__row--total">
            <dt class="result-list__label">{{ __('calculator.resultTotalZakat') }}</dt>
            <span class="result-list__leader" aria-hidden="true"></span>
            <dd class="result-list__value num">{{ \App\Support\MoneyFormatter::format($result->zakatDue) }} {{ $result->currencyCode }}</dd>
        </div>
    </dl>

    <p class="method-note">{{ __('calculator.resultMethodNote') }} <a class="method-note__link" href="{{ route('calculator.about') }}">{{ __('calculator.resultAboutLink') }}</a></p>
@endif
```

- [ ] **Step 5: Add/replace CSS in `resources/css/result.css`** (replace the old `.result-amount*`, `.result-sublabel`, `.result-list__row` rules with these; keep untouched rules):

```css
.result-panel__heading {
    margin: 0 0 1rem;
    color: var(--color-foreground);
    font-size: 1.125rem;
    font-weight: 700;
    letter-spacing: -0.015em;
}

.eyebrow {
    margin: 0 0 0.375rem;
    color: var(--color-muted);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.amount {
    margin: 0 0 0.5rem;
    color: var(--color-foreground);
    font-size: clamp(2.25rem, 8vw, 3.5rem);
    font-weight: 750;
    letter-spacing: -0.035em;
    line-height: 1.05;
    font-variant-numeric: tabular-nums;
}

.amount__currency {
    margin-left: 0.375rem;
    color: var(--color-muted);
    font-size: 1.125rem;
    font-weight: 650;
    letter-spacing: 0;
}

.meter { margin: 1.25rem 0 0; }

.meter__labels {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.375rem;
    color: var(--color-muted);
    font-size: 0.75rem;
    font-variant-numeric: tabular-nums;
}

.meter__track {
    height: var(--meter-height);
    border-radius: 999px;
    background-color: var(--color-primary-soft);
    overflow: hidden;
}

.meter__fill {
    width: var(--meter-width);
    height: 100%;
    border-radius: 999px;
    background-color: var(--color-primary);
}

.meter__ticks { position: relative; height: 1.125rem; }

.meter__tick {
    position: absolute;
    top: 0.25rem;
    left: var(--meter-tick);
    transform: translateX(-50%);
    color: var(--color-muted);
    font-size: 0.6875rem;
    white-space: nowrap;
}

.meter__tick::before {
    content: "";
    position: absolute;
    top: -0.5rem;
    left: 50%;
    width: 1px;
    height: 0.375rem;
    background-color: var(--color-border-strong);
}

.result-list__row {
    display: flex;
    align-items: baseline;
    gap: var(--leader-gap);
    font-size: 0.875rem;
}

.result-list__leader {
    flex: 1;
    border-bottom: 1px dotted var(--color-border-strong);
    transform: translateY(-0.25rem);
}

.result-list__value {
    color: var(--color-foreground);
    font-weight: 600;
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.result-list__row--total .result-list__value {
    color: var(--color-primary);
    font-size: 1rem;
    font-weight: 700;
}

.method-note__link {
    color: var(--color-primary);
    font-weight: 650;
}

.calculator-result { position: relative; overflow: hidden; }

.calculator-result .watermark {
    position: absolute;
    right: -3.5rem;
    top: -3.5rem;
    width: 14rem;
    height: 14rem;
    color: var(--color-primary);
    opacity: var(--watermark-opacity);
    pointer-events: none;
}
```

- [ ] **Step 6: Add the watermark SVG** — insert as the first child of the `<aside id="calculation-result">` in `index.blade.php` (outside the partial so AJAX swaps never duplicate it):

```blade
<svg class="watermark" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
    <path d="M16 2.5 19 13l10.5 3L19 19l-3 10.5L13 19 2.5 16 13 13l3-10.5Z"/>
    <path d="m16 8 1.7 6.3L24 16l-6.3 1.7L16 24l-1.7-6.3L8 16l6.3-1.7L16 8Z"/>
</svg>
```

- [ ] **Step 7: Run tests, expect pass** — `php artisan test --compact tests/Feature/CalculateWebTest.php` (including the existing "identical markup full-page vs AJAX" and below-uruf tests). If the below-uruf ledger test (line 337+) now fails on `'-35.00 g'` — it must still pass: `MoneyFormatter` emits `-35.00`.

- [ ] **Step 8: Full gates + build + commit**

```bash
vendor/bin/pest && vendor/bin/pint --dirty --format agent && composer analyse && npm run build
git add resources/views/calculator resources/css/result.css resources/css/tokens.css lang/en/calculator.php tests/Feature/CalculateWebTest.php
git commit -m "feat(calculator): meter, dotted-leader ledger and refined result panel"
```

---

### Task 5: JS modularization + behavior upgrades

**Files:**
- Create: `resources/js/calculator/dom.ts`, `theme.ts`, `error-renderer.ts`, `form-controller.ts`
- Modify: `resources/js/calculator.ts` (entry only), `tests/Feature/CalculateWebTest.php` (scan test), `resources/views/calculator/index.blade.php` (only if a `data-` hook is missing)

**Interfaces:**
- Consumes: DOM hooks `[data-calculator-form]`, `[data-calculator-result]`, `[data-result-heading]`, `[data-currency-prefix]`, `[data-calculator-reset]`, `[data-field="…"]`, templates `data-{result-loading,result-network-error,result-unexpected-error,validation-summary}-template`.
- Produces: none downstream; browser behavior only.

- [ ] **Step 1: Update the no-money-format scan test first** — replace lines 374–381 of `tests/Feature/CalculateWebTest.php`:

```php
it('never formats money in the browser scripts', function () {
    $scripts = collect(glob(resource_path('js/*.ts')))
        ->merge(glob(resource_path('js/calculator/*.ts')))
        ->map(fn (string $path) => (string) file_get_contents($path))
        ->implode("\n");

    expect($scripts)->not->toContain('toFixed')
        ->and($scripts)->not->toContain('Intl.NumberFormat')
        ->and($scripts)->not->toContain('parseFloat')
        ->and($scripts)->not->toContain('parseInt');
});
```

- [ ] **Step 2: Create `resources/js/calculator/dom.ts`:**

```ts
export function cloneTemplate(selector: string): HTMLElement | null {
    const template = document.querySelector<HTMLTemplateElement>(selector);
    const child = template?.content.firstElementChild;

    return child ? (child.cloneNode(true) as HTMLElement) : null;
}
```

Call sites pass the full CSS attribute selector (e.g. `cloneTemplate('[data-result-loading-template]')`); the function uses it verbatim.

- [ ] **Step 3: Create `resources/js/calculator/theme.ts`** — move `initThemeToggle()` verbatim from the current `calculator.ts` (lines 201–216), exported.

- [ ] **Step 4: Create `resources/js/calculator/error-renderer.ts`:**

```ts
export type FieldErrors = Record<string, string[]>;

const PLAUSIBLE_DECIMAL = /^\d+([.,]\d{1,2})?$/;

export class ErrorRenderer {
    private readonly form: HTMLFormElement;
    private readonly initialDescriptions = new Map<HTMLElement, string>();

    constructor(form: HTMLFormElement) {
        this.form = form;
        form.querySelectorAll<HTMLElement>('[aria-describedby]').forEach((element) => {
            this.initialDescriptions.set(element, element.getAttribute('aria-describedby') ?? '');
        });
    }

    render(errors: FieldErrors, summaryTemplate: HTMLElement | null): void {
        this.clear();

        for (const [field, messages] of Object.entries(errors)) {
            this.markInvalid(field, messages[0] ?? '');
        }

        const summary = summaryTemplate;
        const list = summary?.querySelector('ul');

        if (! summary || ! list) {
            return;
        }

        for (const field of Object.keys(errors)) {
            list.append(this.summaryItem(field));
        }

        this.form.before(summary);
        summary.focus();
    }

    clear(): void {
        document.querySelectorAll('[data-validation-summary]').forEach((element) => element.remove());
        this.form.querySelectorAll('[data-field-error]').forEach((element) => element.remove());

        for (const [element, describedBy] of this.initialDescriptions) {
            element.setAttribute('aria-describedby', describedBy);
        }

        this.form.querySelectorAll<HTMLElement>('[aria-describedby]').forEach((element) => {
            if (! this.initialDescriptions.has(element)) {
                element.removeAttribute('aria-describedby');
            }
        });

        this.form.querySelectorAll<HTMLElement>('[aria-invalid]').forEach((element) => element.removeAttribute('aria-invalid'));
    }

    private controlFor(container: HTMLElement): HTMLElement | null {
        return container instanceof HTMLFieldSetElement
            ? container.querySelector<HTMLElement>('input')
            : container.querySelector<HTMLElement>('input, select, textarea');
    }

    private markInvalid(field: string, message: string): void {
        const container = this.form.querySelector<HTMLElement>(`[data-field="${field}"]`);

        if (! container) {
            return;
        }

        const control = this.controlFor(container);

        if (control) {
            const describedBy = new Set((control.getAttribute('aria-describedby') ?? '').split(/\s+/).filter(Boolean));
            describedBy.add(`${field}-error`);
            control.setAttribute('aria-invalid', 'true');
            control.setAttribute('aria-describedby', [...describedBy].join(' '));
            control.addEventListener('input', () => this.dismissIfPlausible(field, control));
        }

        const error = document.createElement('p');
        error.id = `${field}-error`;
        error.className = 'field__error';
        error.setAttribute('role', 'alert');
        error.dataset.fieldError = '';
        error.textContent = message;
        container.append(error);
    }

    private dismissIfPlausible(field: string, control: HTMLElement): void {
        const input = control instanceof HTMLInputElement ? control : null;

        if (! input || ! PLAUSIBLE_DECIMAL.test(input.value.trim())) {
            return;
        }

        document.getElementById(`${field}-error`)?.remove();
        control.removeAttribute('aria-invalid');
        const describedBy = (control.getAttribute('aria-describedby') ?? '')
            .split(/\s+/)
            .filter((id) => id !== `${field}-error`)
            .join(' ');
        describedBy ? control.setAttribute('aria-describedby', describedBy) : control.removeAttribute('aria-describedby');
    }

    private summaryItem(field: string): HTMLLIElement {
        const container = this.form.querySelector<HTMLElement>(`[data-field="${field}"]`);
        const label = container?.querySelector('legend, label')?.textContent?.trim() ?? field;
        const link = document.createElement('a');
        link.href = `#${field}`;
        link.textContent = label;
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const control = container ? this.controlFor(container) : null;
            (control ?? container)?.focus();
        });

        const item = document.createElement('li');
        item.append(link);

        return item;
    }
}
```

- [ ] **Step 5: Create `resources/js/calculator/form-controller.ts`:**

```ts
import { cloneTemplate } from './dom';
import { ErrorRenderer, type FieldErrors } from './error-renderer';

export function initCalculatorForm(): void {
    const form = document.querySelector<HTMLFormElement>('[data-calculator-form]');
    const resultRegion = document.querySelector<HTMLElement>('[data-calculator-result]');

    if (! form || ! resultRegion) {
        return;
    }

    const renderer = new ErrorRenderer(form);
    const submitButton = form.querySelector<HTMLButtonElement>('button[type="submit"]');
    const submitLabel = submitButton?.textContent ?? '';
    const prefix = document.querySelector<HTMLElement>('[data-currency-prefix]');
    const currencySelect = form.querySelector<HTMLSelectElement>('select[name="currency"]');
    const initialResultHtml = resultRegion.innerHTML;
    let isSubmitting = false;

    form.addEventListener('submit', (event) => {
        void submit(event);
    });

    currencySelect?.addEventListener('change', () => {
        if (prefix) {
            prefix.textContent = currencySelect.value;
        }
    });

    document.querySelector('[data-calculator-reset]')?.addEventListener('click', (event) => {
        event.preventDefault();
        renderer.clear();
        form.reset();
        resultRegion.innerHTML = initialResultHtml;

        if (prefix && currencySelect) {
            prefix.textContent = currencySelect.value;
        }
    });

    async function submit(event: Event): Promise<void> {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        isSubmitting = true;
        setSubmitting(true);

        const previousResult = resultRegion.innerHTML;
        renderTemplate('[data-result-loading-template]');
        resultRegion.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: buildRequestBody(form),
            });

            if (response.ok) {
                renderer.clear();
                resultRegion.innerHTML = await response.text();
                handoff();
            } else if (response.status === 422) {
                renderer.render(await readValidationErrors(response), cloneTemplate('[data-validation-summary-template]'));
                resultRegion.innerHTML = previousResult;
            } else {
                renderTemplate('[data-result-unexpected-error-template]');
            }
        } catch {
            renderTemplate('[data-result-network-error-template]');
        } finally {
            isSubmitting = false;
            setSubmitting(false);
            resultRegion.setAttribute('aria-busy', 'false');
        }
    }

    function handoff(): void {
        const rect = resultRegion.getBoundingClientRect();

        if (rect.top < 0 || rect.bottom > window.innerHeight) {
            resultRegion.scrollIntoView({ block: 'nearest' });
        }

        resultRegion.querySelector<HTMLElement>('[data-result-heading]')?.focus();
    }

    function setSubmitting(isBusy: boolean): void {
        if (! submitButton) {
            return;
        }

        submitButton.disabled = isBusy;
        submitButton.textContent = isBusy
            ? submitButton.dataset.submittingText ?? submitLabel
            : submitButton.dataset.submitText ?? submitLabel;
    }

    function renderTemplate(selector: string): void {
        const node = cloneTemplate(selector);

        if (node) {
            resultRegion.replaceChildren(node);
        }
    }
}

function buildRequestBody(form: HTMLFormElement): URLSearchParams {
    const body = new URLSearchParams();

    for (const [key, value] of new FormData(form)) {
        if (typeof value === 'string') {
            body.append(key, value);
        }
    }

    return body;
}

async function readValidationErrors(response: Response): Promise<FieldErrors> {
    const payload = await response.json() as { errors?: FieldErrors };

    return payload.errors ?? {};
}
```

- [ ] **Step 6: Replace `resources/js/calculator.ts` with the entry:**

```ts
import { initCalculatorForm } from './calculator/form-controller';
import { initThemeToggle } from './calculator/theme';

document.addEventListener('DOMContentLoaded', () => {
    initCalculatorForm();
    initThemeToggle();
});
```

- [ ] **Step 7: Verify** — `npm run build` (esbuild resolves the module graph), then `php artisan test --compact tests/Feature/CalculateWebTest.php` (scan test passes over all module files). Browser QA of every behavior is Task 6; here, manually confirm in devtools: submit → swap + focus; force a 422 → summary; click summary link → field focused; type `120` into flagged weight → error disappears; switch currency → prefix chip updates; Reset → form + result back to initial; resize narrow → scroll handoff fires.

- [ ] **Step 8: Commit**

```bash
git add resources/js tests/Feature/CalculateWebTest.php
git commit -m "refactor(js): modular calculator client with handoff, focus, reset and stale-error dismissal"
```

---

### Task 6: QA matrix, docs amendment, full-suite close-out

**Files:**
- Modify: `ARCHITECTURE.md` (§9 only)
- (no code changes expected; fixes go back into the owning task's files)

- [ ] **Step 1: Full backend gates** — `php artisan test --compact` (all green), `vendor/bin/pint --dirty --format agent`, `composer analyse` (zero new errors).
- [ ] **Step 2: `npm run build`** and serve; run the QA matrix in a real browser (DevTools device emulation at 390px + desktop 1280px, both themes): empty → result → 422 → corrected submit; below-uruf case; JS disabled (DevTools) full POST path incl. error re-render; keyboard-only flow (tab order, focus rings, summary link focus, Enter submits); meter visible and correct at 120@350; console clean on every step; tap targets ≥44px; contrast spot-check badge/meter/tick text in both themes.
- [ ] **Step 3: Amend `ARCHITECTURE.md` §9** — append three rows to the token table: meter (pure-CSS bar, Blade-computed `--meter-width/--meter-tick`, no animation), money display (`App\Support\MoneyFormatter`, grouped, locale-pinned `en`, API stays raw), CSS architecture (token/base/component partial chain, Tailwind import removed, dependency packages untouched). No other doc edits.
- [ ] **Step 4: Commit**

```bash
git add ARCHITECTURE.md
git commit -m "docs: record frontend overhaul design amendments in architecture section 9"
```

- [ ] **Step 5: Cleanup** — remove scratch servers if still running (artisan serve, mockup `php -S`); `tasks/mockups/` stays committed as the approved visual contract.
