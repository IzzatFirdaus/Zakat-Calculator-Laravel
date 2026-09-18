# Spec — Zakat Calculator Frontend Overhaul (Variant A: Refined Civic)

- Date: 2026-09-18
- Status: Approved for spec review (visual direction A + Approach 1 confirmed by user)
- Reference mockup: `tasks/mockups/a-refined-civic.html` (approved visual contract)
- Governing docs: `PRD.md`, `ARCHITECTURE.md` (§9 design record, §10 non-negotiables), `AGENTS.md`

## 1. Context and audit summary

A live browser audit (empty / result / 422 / theme / About / console) found the v1.0 frontend functionally sound — correct golden values, working progressive enhancement, clean console — with these concrete deficiencies:

| # | Finding | Severity |
|---|---|---|
| F1 | 963-line single `resources/css/app.css`; 217-line single closure-heavy `calculator.ts` | Architecture |
| F2 | `@import 'tailwindcss'` + `@source` lines are dead weight — zero utility classes used anywhere | Architecture |
| F3 | No unit affordances in inputs (`g` suffix, currency prefix); unit info only in help text | UX |
| F4 | Money rendered without thousands separators (`12250.00`) | UX |
| F5 | On mobile the result panel lands far below the fold with no scroll/focus handoff | UX |
| F6 | Error-summary links jump to anchors but do not focus the field | A11y |
| F7 | "Reset" is a GET link that reloads the page, not a form reset | UX |
| F8 | After a 422, field errors persist visually until the next full submit | UX |
| F9 | Faux-heavy font weights (760/780) on the system stack; flat panel hierarchy; result panel lacks "moment" | Visual |
| F10 | Category cards do not surface the uruf threshold at decision time | UX |

## 2. Decisions

1. **Visual direction: A — Refined Civic.** Elevation of the existing emerald/warm-stone system per `ARCHITECTURE.md` §9 (ENERGY 1 / RHYTHM 1 / MOTION 1). Approved via side-by-side mockups (`tasks/mockups/`).
2. **Technical approach: in-place progressive refactor.** Stack unchanged: Blade + vanilla TypeScript + hand-authored CSS with token custom properties. No new runtime or dev dependencies. Tailwind/Alpine/htmx explicitly rejected.
3. **Localization convention:** grouped `lang/en/*.php` files with `__('calculator.…')` keys (current repo convention; keys were already migrated out of `lang/en.json`). All new copy follows this. The user's original "en.json" instruction is superseded by the repo convention per AGENTS.md.
4. **Money formatting is server-side only** (Blade presentation layer). JSON API contract keeps raw numeric strings. Client JS never re-formats money (`AGENTS.md` domain rule 7).
5. **Domain integrity unchanged:** `app/Domain/Zakat/**` stays pure PHP (arch test enforced), `brick\math\BigDecimal`, scale 2, `HALF_UP` at final step only; validation and normalization stay solely in `CalculateZakatRequest`; golden values must not change.

## 3. Scope

**In scope:** everything in §1 findings F1–F10.
**Out of scope (v1.0 constraints):** input persistence, live gold prices, analytics/telemetry, additional locales, component framework, `tsc --noEmit` CI step (new dev dependency — not approved), redesign of About content (token alignment only), removing `tailwindcss`/`@tailwindcss/vite` from `package.json` (dependency change — only the CSS import is removed).

## 4. CSS architecture

`resources/css/app.css` becomes a thin entry point that only imports, in order:

| File | Contents |
|---|---|
| `resources/css/tokens.css` | All custom properties: light palette on `:root`, dark overrides on `html.dark`, radii, shadows, spacing rhythm. Single source of design truth. Updated palette per mockup A (e.g. `--bg:#f4f2ea`, `--accent:#0b6b57`; dark `--accent:#3fd6a2`). |
| `resources/css/base.css` | Reset, body/typography, `::selection`, `:focus-visible`, skip-link, `prefers-reduced-motion` block, `.num` tabular-nums utility class. |
| `resources/css/header.css` | `.app-header*`, theme toggle, nav states. |
| `resources/css/calculator.css` | `.calculator-*`, `.field*`, `.input`, `.select`, `.control` unit/prefix slots, `.category-*` cards incl. `.badge`, `.error-summary*`, `.actions`, `.button-*`. |
| `resources/css/result.css` | `.calculator-result`, `.eyebrow`, `.amount*`, `.meter*` (track/fill/ticks), `.result-list` dotted-leader rows, `.status-panel*`, `.result-state*`, `.result .watermark`. |
| `resources/css/about.css` | `.methodology*` (restyled to shared tokens; content unchanged). |
| `resources/css/footer.css` | `.app-footer`. |

- `@import 'tailwindcss';` and all `@source` lines are deleted.
- BEM naming retained throughout; Vite already bundles CSS `@import` chains (no build-config change).
- Contrast floor WCAG AA maintained in both themes for every new token pair (badge text, meter labels, dotted-leader values).
- Transitions ≤ 150 ms; meter renders statically (no animation); reduced-motion block preserved.

## 5. Blade / component design

### 5.1 Form (`resources/views/calculator/index.blade.php`)

- **Weight field:** text input wrapped in `.control` with a static `g` suffix element (aria-hidden; accessible name unchanged — label already says "(grams)" via `calculator.weightLabel`).
- **Value field:** `.control` with a currency-code prefix chip rendered by Blade from the currently selected currency (`old('currency', 'MYR')`); JS updates the chip text on `change` of the select (presentation only, no formatting).
- **Category cards:** each gains a badge with the uruf threshold. New keys: `calculator.categoryKeptUruf` = "uruf 85 g", `calculator.categoryWornUruf` = "uruf 200 g". Static copy; the authoritative thresholds remain in `StandardUrufRule` (a feature test asserts badge text matches the rule constants so they cannot drift silently).
- **Result panel:** eyebrow (`calculator.resultStatus`), hero amount `.amount` (formatted, §6), uruf note line, **zakatable-weight meter**, dotted-leader `<dl>` ledger (existing four rows), method note with link to About.
- **Meter:** pure CSS bar. Width % = `weightMinusUruf ÷ (weightMinusUruf + urufGrams)` computed **in the Blade partial** from `ZakatResult` BigDecimals (single division at scale 4, cast to float only for the CSS custom property `--meter-width`). This is presentational geometry, not the zakat formula; no domain logic moves to the view. `role="img"` + `aria-label` from a new key `calculator.meterLabel` with `:weight/:uruf/:zakatable` placeholders. Visible tick labels use `calculator.meterUrufTick` (`:uruf` placeholder) and the total weight figure; below-uruf results hide the meter and keep the existing status panel.
- **Watermark:** decorative khatam SVG in the result card, `aria-hidden="true"`, opacity token.
- Empty / loading / error / below-uruf states keep their existing partial structure and templates, restyled via new classes only.

### 5.2 Layout and About

- `layouts/app.blade.php`: markup unchanged except new CSS entry imports are automatic (same `app.css` path — no layout diff).
- `about.blade.php`: no structural change; `.methodology` classes pick up refined tokens.

### 5.3 Localization

All new strings go to `lang/en/calculator.php` (and `layout.php`/`app.php` only if needed). No hard-coded UI text in Blade or TS (unchanged rule).

## 6. Money formatting (the single backend touch)

- New `app/Support/MoneyFormatter.php`: `final class MoneyFormatter` with `public static function format(Brick\Math\BigDecimal $amount, string $currencyCode): string` → `number_format`-equivalent via `NumberFormatter` (locale `en`, `DECIMAL_ALWAYS_SHOWN`, min/max fraction digits 2). Output e.g. `12,250.00`. Currency code is appended by the view as today (code, not symbol — MYR/USD stay unambiguous).
- Used only inside `resources/views/calculator/partials/result.blade.php` (both AJAX fragment and full-page render share this partial, so one change covers both channels).
- `CalculatorController::apiCalculate()` untouched — JSON keeps raw `BigDecimal::toString()` values.
- `app/Domain/Zakat/**` untouched. `app/Support/` is a new namespace directory inside `app/` (standard Laravel convention); this spec entry is the required approval record. AGENTS.md file-policy table gains `app/Support/MoneyFormatter.php` as active surface.

## 7. JS architecture

`resources/js/app.ts` unchanged (imports `bootstrap` + `calculator`). `resources/js/calculator.ts` becomes the composition entry; logic moves to:

| Module | Responsibility |
|---|---|
| `resources/js/calculator/dom.ts` | Typed query helpers (`requireElement`, template cloning). |
| `resources/js/calculator/theme.ts` | Existing toggle behavior, moved verbatim. |
| `resources/js/calculator/error-renderer.ts` | 422 → per-field errors + summary; **new:** summary links `preventDefault` + `.focus()` the control; **new:** after a 422, `input` on a flagged field removes that field's error node once the value is non-empty and matches `/^\d+([.,]\d{1,2})?$/` (stale-error dismissal only — never blocks submit; server remains the sole authority). |
| `resources/js/calculator/form-controller.ts` | Submit lifecycle (fetch, aria-busy, swap), loading/error templates, **new:** post-swap viewport handoff — if the result panel's bounding rect is outside the viewport, `scrollIntoView({ block: 'nearest' })` before focusing `[data-result-heading]`; **new:** currency-prefix chip sync on select change; **new:** reset — converts the reset link to a client reset (clear inputs to defaults, restore empty-state partial via `GET` of the form action is NOT used; instead resets the form element and swaps the cached initial result-partial HTML captured at load). No-JS keeps the existing GET link. |

- Fetch/CSRF mechanics unchanged (FormData includes `_token`; `X-Requested-With` header).
- No external dependencies; no framework; TS only (esbuild via Vite, as today).
- Every behavior above must degrade to the current server-rendered path with JS disabled.

## 8. Error handling

- All existing channels preserved: server 422 re-render (no-JS), AJAX 422 JSON errors, network failure template, unexpected-status template, empty state, below-uruf state.
- The 422 JSON shape and status codes do not change.
- `MoneyFormatter` receives only domain `BigDecimal` values — no null-handling branches; the partial already guards `$result === null`.

## 9. Testing

Written **before** implementation (AGENTS.md checklist), Pest, narrowest-first:

1. `tests/Feature/CalculateWebTest.php` — update markup assertions for new classes/structure; **new** assertions: formatted `12,250.00` appears for the golden 120@350 case; meter `--meter-width` custom property present; badge text `uruf 85 g` / `uruf 200 g`; badge copy equals `StandardUrufRule` thresholds; upper-bound `1,000,000` g renders with separators.
2. **New** `tests/Unit/Support/MoneyFormatterTest.php` — `0.5 → "0.50"`, `12250 → "12,250.00"`, `1000000-scale max → "1,000,000.00…"`, HALF_UP scale-2 parity with `ZakatCalculator::SCALE` output.
3. `tests/Feature/CalculateApiTest.php` — assert raw numeric strings unchanged (regression guard for decision 4).
4. `tests/Unit/Domain/Zakat/**` — untouched; must stay green (golden values).
5. Arch test — must stay green (`app/Domain` purity; `app/Support` is outside it).
6. Browser QA (manual, both themes): empty → result → 422 → corrected submit; JS-off POST path; mobile-width result handoff; keyboard-only pass incl. error-summary focus; console clean; `npm run build` green.

Quality gates: `php artisan test --compact` → full `vendor/bin/pest` → `vendor/bin/pint --dirty --format agent` → `composer analyse` (zero new errors) → `npm run build`.

## 10. Implementation slices (order)

1. CSS split with **zero visual change** (verify build + smoke tests).
2. `MoneyFormatter` + tests + result partial formatting.
3. Blade redesign (form affordances, badges, meter, ledger, watermark) + `tokens.css` palette + lang keys + component CSS files.
4. JS module decomposition + behavior upgrades (handoff, focus, reset, stale-error dismissal, prefix sync).
5. Full QA pass + `ARCHITECTURE.md` §9 amendment note (tokens/meter/formatter) — docs updated only for what the shipped change makes wrong.

Each slice ends green at its narrowest gate before the next begins.

## 11. Risks

| Risk | Mitigation |
|---|---|
| Badge copy drifts from real uruf values | Feature test pins badge text to `StandardUrufRule` constants. |
| View-layer meter math misread as formula duplication | Meter width derived only from existing `ZakatResult` fields; no new arithmetic on inputs; documented in §5.1. |
| JS reset diverging from no-JS behavior | Reset restores the exact initial DOM captured at load; no-JS path unchanged. |
| Tailwind import removal breaking an unnoticed utility use | Grep for utility-class patterns pre/post; build + full suite gate slice 1. |
| Locale-dependent `NumberFormatter` output drift | Formatter pins locale `en` explicitly, independent of app locale. |
