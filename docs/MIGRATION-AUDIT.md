# Migration Audit: Android Reference vs Laravel Web App

> **Scope:** feature and code reconciliation between `D:\Projects\Zakat-Calculator` (legacy Android, shipped v1.0) and this repository (Laravel 13 web v1.0).
> **Mode:** read-only audit + live QA. No product code was modified. Patches below are proposals.
> **Date:** 2026-09-18. **Auditor:** agent session, evidence-backed (file:line, test runs, live browser session).

## Verdict

The web migration is faithful and, in most dimensions, exceeds the Android original. Domain math matches the shipped Android behavior exactly (golden values re-verified live), copy was migrated with only two deliberate rewordings, all live Android UI states have web equivalents, and accessibility/no-JS handling is strictly better. Findings are 1 required test-coverage gap, 1 tooling gate that is red at baseline, 1 rule violation (one-line fix), and a small set of owner decisions.

---

## 0. Critical context: which Android behavior is "the reference"

The Android repo contains **three contradictory descriptions of the calculation**. Only one shipped:

| Layer | Model | Evidence | Shipped? |
|---|---|---|---|
| **A. MainActivity inline math** | `(weight − uruf) × price × 2.5%`; uruf 85 kept / 200 worn; `≤ 0 → 0`; `double` math | `MainActivity.java:75-119` | **Yes — this is the product** |
| B. `domain/` package + unit tests | Nisab threshold on **full** weight (≥ 85g), zakat on total value, no uruf deduction; asserts 100g @ 60 → **150.00** | `ZakatCalculator.java:36-64`, `ZakatCalculatorTest.java:21-33` | No — unreferenced by both Activities |
| C. Android `PRD.md` | "Total gold value + Nisab comparison" display, single 85g threshold | Android `PRD.md:37-39,82-86` | No — never displayed |

The Laravel port follows **A**, which the Laravel docs pin with golden values (`AGENTS.md` domain rules, `ARCHITECTURE.md` §4.3). For the same input (100 KEPT @ 60) model B would produce 150.00 and the web app produces 22.50. **Do not "fix" the web app toward model B.** The entire Android `domain/`, `util/`, and `ui/` packages (10 of 12 Java files) are dead code — `MainActivity` imports none of them.

---

## 1. Domain & business logic audit — PASS

| Check | Android (shipped) | Laravel | Verdict |
|---|---|---|---|
| Formula | `(w − uruf) × v`, `ruf ∈ {85,200}` | `ZakatCalculator.php:26-49` identical | Match |
| Rate | `0.025` | `ZakatCalculator::ZAKAT_RATE` | Match |
| Below/exact uruf | `goldWeightMinusX <= 0 → 0` (`MainActivity.java:104`) | `isLessThanOrEqualTo(0) → 0` (`ZakatCalculator.php:30-34`) | Match |
| Rounding | `%.2f` display only | `toScale(2, HalfUp)` at final step + display | Match (web stricter) |
| Golden 1: 120 KEPT @ 350 | 35.00 / 12250.00 / 306.25 | asserted in `ZakatCalculatorTest.php:9-21`; **verified live in browser** | Match |
| Golden 2: 50 KEPT @ 350 | below uruf / 0 | asserted `:36-59`; **verified live** | Match |
| Golden 3: 210 WORN @ 350 | 10.00 / 3500.00 / 87.50 | **verified live; NOT asserted in any test** → gap G1 | Behavior matches, guard missing |
| Boundary: exactly 85 KEPT | 0.00, zakat not due | asserted + **verified live** | Match |
| Negative / zero / empty | blocked (toast) | blocked (inline errors, `gt:0`, regex) | Match (web better UX) |
| Precision | IEEE `double` (e.g. 120.555 − 85 → `35.55` at `%.2f`) | `BigDecimal`, scale-2 HALF_UP at final step (`35.56`) | Web **exceeds** — intentional, documented in `PRD.md` §5 |
| Upper bounds | none | weight ≤ 1,000,000; value ≤ 10,000,000 (`CalculateZakatRequest.php:48-49`) | Web exceeds |
| Decimal separators | locale-dependent `Double.parseDouble` | dot/comma normalized once (`:76-89`) | Web exceeds |
| Implicit `totalValue` (w × v) | only in dead model B | not ported | **No action** — shipped Android UI never showed it; Laravel PRD F5/F7 don't require it |
| Category after Reset | radios cleared → next calc silently treated as WORN (latent Android bug, `MainActivity.java:40,128-129`) | category required, `kept` default | Web fixed a latent bug |

### Findings

- **G1 (Required, test coverage).** The WORN golden value — declared a never-silently-change golden in `AGENTS.md` — has no test. `grep` over `tests/` finds WORN only in the uruf-table assertion (`StandardUrufRuleTest.php:11`). The calculation path for WORN (200g uruf) is otherwise unguarded.
- **G7 (Nit).** Android `Double.parseDouble` accepted `+120`; the web regex rejects it (`CalculateZakatRequest.php:48`). `BigDecimal::of('+5')` works fine (verified via tinker), so parity is a one-character regex change if desired.
- **FYI.** Android model B (`ZakatResult.totalValue`, `meetsNisab`) was correctly not ported; record this in the PRD so future audits don't resurrect it (see P7).

---

## 2. UI/UX & copy reconciliation

### Component parity

| Android shipped component | Laravel equivalent | Verdict |
|---|---|---|
| Weight input, label "Weight of Gold (grams)", hint "Enter weight", decimal keypad, autofill hints | `index.blade.php:50-68` label/placeholder/help + `inputmode="decimal"` + comma support | Exceeds (autofill hints dropped; `autocomplete="off"` is the web convention) |
| Value input "(RM)" hard-coded in label | value field + currency `<select>` (9 ISO codes, MYR default) | Documented web extension (PRD F4); RM label intentionally replaced |
| Category radios "Keep"/"Wear", "Keep" default-checked | radio cards "Kept (savings)"/"Worn (jewelry)" + descriptions, kept default | Exceeds |
| "Calculate" button | submit button | Match |
| "Reset" button (clears fields + radios + labels) | reset link → fresh GET `/` | Equivalent, no-JS safe |
| Result: "Zakat Calculation result:" + 3 bold labels | result panel: heading, focal amount, uruf applied, minus-uruf, payable, total, method note (`partials/result.blade.php`) | Exceeds |
| Below uruf: shows negative `w−uruf` and 0.00 money | below-uruf state panel + same breakdown; test asserts no negative money | Exceeds |
| **"Share App" button + menu item + share sheet** | **absent** (`grep` finds no share in views/lang/TS) | **G3 — owner decision** |
| Overflow menu: About, Share | header nav: Calculator, About (Share absent) | Partial (covered by G3) |
| About screen: heading, app name, "Version 1.0", description, "Copyright 2023 2021601074", "Visit Website" → GitHub URL | About page: methodology, formula + example, disclaimer, source link → **same GitHub URL** (`about.blade.php:49`) | Partial: version + copyright copy absent → **G6** |
| System dark mode (Material3 DayNight); manual toggle class existed but was never wired in Android | system default + manual toggle, persisted, `aria-pressed` (verified live, both themes) | Exceeds |
| Toast errors (3 hardcoded messages) | inline field errors + summary with anchors + `aria-live`, focus moved to summary (verified live) | Exceeds |
| TalkBack labels, 48dp targets | skip link, landmarks, `aria-describedby`/`aria-invalid`, 44px+ targets, focus management | Exceeds |
| Empty state | explicit empty state panel | Exceeds |

### Copy mapping (Android live strings → `lang/en/*.php`)

| Android live string | Laravel key | Status |
|---|---|---|
| Weight of Gold (grams) / Enter weight | `calculator.weightLabel` / `weightPlaceholder` | Exact |
| Current Gold Value per Gram (RM) | `calculator.valueLabel` (+ currency selector) | RM replaced by selector (deliberate) |
| Select Gold type / Keep / Wear | `calculator.categoryLabel` / `categoryKept` / `categoryWorn` | Reworded richer: "Kept (savings)", "Worn (jewelry)" |
| Calculate / Reset | `calculator.submit` / `reset` | Exact |
| Gold Weight minus uruf (g): | `calculator.resultWeightMinusUruf` | Exact (colon dropped, layout handles) |
| Gold Value for Zakat Payable (RM): | `calculator.resultPayableValue` | Exact (RM → currency suffix) |
| Total Zakat (RM): | `calculator.resultTotalZakat` | Exact |
| Zakat Calculation result: | `calculator.resultHeading` ("Calculation Result") | Close reword |
| Weight field cannot be empty. / Value field cannot be empty. / Weight must be a positive number. / Value must be a positive number. | `calculator.errorWeightEmpty` etc. `calculator.php:27-34` | **Exact** — the web adopted the Android `strings.xml` texts (which Android's own UI never used; its toasts were hardcoded) |
| Toast texts ("Please enter weight and value!") | `errorSummary` + per-field errors | Superseded by better UX |
| `share_app`, `share_app_subject`, `share_app_text` | — | **G3** |
| Version 1.0, Copyright 2023 2021601074, app description | — (intro reworded to methodology copy) | **G6** |
| `weight_input_hint`/`value_input_hint`, `message_no_result`, `section_*`, `button_visit_website`, error strings, `about_title`, `about_text_2`, `toolbar_description` | n/a | Dead in Android (never referenced); no action |

Note: Android's share copy was itself broken (subject/text concatenation, no URL — `MainActivity.java:132-141`). If Share is ported, write new copy; do not port the strings.

### Findings

- **G4 (Required, one line).** Hard-coded UI text in a Blade file: `resources/views/layouts/app.blade.php:35` → `aria-label="Primary"`. Violates ARCHITECTURE rule 7 ("no hard-coded UI text in Blade or JS"). It is the only offender (sweep of all views).
- **G3 (Medium, owner decision).** Share App is the one shipped Android UI component with no web counterpart and no recorded decision. Laravel `PRD.md` §2.1/§2.2 never mentions it.
- **G6 (Low, owner decision).** About copy dropped: "Version 1.0" and the copyright line. (Copyright contains a personal ID string; dropping may be deliberate — decide.)
- **Nit.** Android's theme cycle (dead `ThemeController`) had 3 states (light→dark→system); the web toggle is light↔dark with system as the initial default and no UI path back to "system" (only clearing `localStorage`). Acceptable; a 3-state cycle adds UI for no user value.
- **G8 (Nit, hygiene).** `resources/views/welcome.blade.php` is a stock scaffold leftover (documented as intentional in `ARCHITECTURE.md` §2). Unreachable; delete only with approval.
- **FYI.** `lang/en.json` does not exist and should not: keys live in grouped `lang/en/*.php` (`calculator.php:4` documents the move; `ARCHITECTURE-ESSENTIALS.md` §4.3 records the en.json removal). All copy centralization otherwise verified.

---

## 3. Assets

- Android ships **no brand assets**: only stock Android Studio launcher art (adaptive icon, default robot forevector, PNG mipmaps). Nothing was left behind.
- Web: header brand is the inline khatam star SVG (`layouts/app.blade.php:29-32`); favicon is **`public/favicon.ico` = 0 bytes** → **G5 (Optional)**. An empty file serves an empty 200 to browsers; the page emits no `<link rel="icon">` at all.

---

## 4. Routes & API

| Surface | Verified |
|---|---|
| `GET /` | Live: renders form, empty state, currency list |
| `POST /calculate` (no-JS) | Test suite: redirect-back-with-errors on invalid; 200 full page on valid (`CalculateWebTest.php`) |
| `POST /calculate` (AJAX) | Live: 200 + partial, focus moves to result heading; byte-identical to full-page fragment (tested) |
| `GET /about` | Live: methodology, formula, example, disclaimer, source link |
| `POST /api/v1/calculate` | Live: 200 with `{uruf_grams, weight_minus_uruf, below_uruf, payable_value, zakat_due, zakat_rate, currency_code}`; 422 with localized messages on negative weight + bad currency |

Android had no API; this is additive. Response shape matches `ARCHITECTURE.md` §3.

---

## 5. Tooling & quality gates

| Gate | Result |
|---|---|
| `php artisan test --compact` | **48 passed (194 assertions)** |
| `vendor/bin/phpstan analyse` (level 6, paths=app) | **12 errors** — all in pre-existing skill-automation files: `app/Console/Commands/ManageSkills.php` (2), `app/Services/SkillExecutor.php` (6), `app/Services/SkillRegistry.php` (4). Zero in the calculator surface. → **G2** |
| `vendor/bin/pint` | Not run — audit made no code changes |
| Browser QA | Light + dark screenshots captured; console has no JS errors (only intentional 422 network logs); Vite HMR connected |
| Git | `main` has **no commits yet**; the tree is staged/untracked. No baseline to diff against. |

- **G2 (Required, tooling).** `phpstan.neon:18-23` has an empty `ignoreErrors` and a commented-out `baseline:` line; the 12 pre-existing errors therefore fail `composer analyse`. The documented gate ("commerce analyse, zero new Larastan errors") cannot currently pass. The config file itself names the intended fix (generate baseline).
- **FYI.** `public/hot` exists locally (content `http://[::1]:5173`) and is gitignored; CSS requires `npm run dev` (or `npm run build`) — standard Vite behavior, not a defect.

---

## 6. Gap register

| ID | Severity | Gap | Evidence |
|---|---|---|---|
| G1 | **Required** | WORN golden (210 @ 350 → 87.50) asserted nowhere | `tests/` grep; `ZakatCalculatorTest.php` has no WORN case |
| G2 | **Required** | Static-analysis gate red at baseline (12 pre-existing errors, baseline not wired) | `phpstan.neon:18-23`; phpstan output |
| G4 | **Required** | Hard-coded `aria-label="Primary"` in Blade (rule 7 violation) | `layouts/app.blade.php:35` |
| G3 | Medium (owner) | Share App component unmigrated and undecided | Android `MainActivity.java:45,63-71,132-142`; absent in web |
| G5 | Optional | `favicon.ico` is 0 bytes; no `<link rel="icon">` | `ls -la public/favicon.ico` → 0 bytes |
| G6 | Optional (owner) | About: version + copyright copy dropped | Android `strings.xml:57,60`; `about.blade.php` |
| G7 | Nit | Leading `+` in numeric input rejected (Android accepted) | `CalculateZakatRequest.php:48-49` |
| G8 | Nit | Unused `welcome.blade.php` scaffold (documented intentional) | `ARCHITECTURE.md` §2 |
| FYI | Info | Android model-B domain classes are dead; document so they are never used as reference | Section 0 above |

---

## 7. Action plan (discrete, minimal-diff)

Apply in order. After each patch: `php artisan test --compact` on the affected file, then `vendor/bin/pint --dirty --format agent`. Land as the first reviewed commit(s) — the branch has no history yet.

### P1 — Guard the WORN golden (closes G1)
`tests/Unit/Domain/Zakat/ZakatCalculatorTest.php`, append:

```php
it('matches the Android reference golden values for worn gold', function () {
    $input = new ZakatInput(
        weightGrams: BigDecimal::of('210'),
        category: GoldCategory::WORN,
        valuePerGram: BigDecimal::of('350'),
        currencyCode: 'MYR',
    );
    $result = ZakatCalculator::calculate($input, new StandardUrufRule);

    expect($result->urufGrams->toString())->toBe('200')
        ->and($result->weightMinusUruf->toScale(2)->toString())->toBe('10.00')
        ->and($result->payableValue->toScale(2)->toString())->toBe('3500.00')
        ->and($result->zakatDue->toString())->toBe('87.50');
});
```

Optional same-pattern case for the exact WORN boundary (`200` → below uruf, `0.00`). Diff: +14 lines, one file.

### P2 — Wire the PHPStan baseline (closes G2)
Do **not** edit the skill-automation sources (repo rule: treat as infrastructure). Instead:

```bash
vendor/bin/phpstan analyse --generate-baseline   # writes phpstan-baseline.neon (12 infra errors)
```

then in `phpstan.neon` uncomment `baseline: phpstan-baseline.neon` (line 23). Diff: one generated file + one uncommented line. Verify: `composer analyse` exits 0; a new domain error still fails (baseline only records the known 12).

### P3 — Move the hard-coded aria-label into lang (closes G4)
- `lang/en/layout.php`: add `'primaryNav' => 'Primary',`
- `resources/views/layouts/app.blade.php:35`: `aria-label="{{ __('layout.primaryNav') }}"`

Diff: +1 line, 1 line changed. Verify: `php artisan test --compact --filter="renders the calculator form"`.

### P4 — Share App: decide, then act (closes G3)
- **Option A (recommended, smallest):** document the drop — add a line to `PRD.md` §2.2 Out of scope: "Result/app sharing (Android share-sheet affordance); revisit with a Web Share API pass." Diff: 1 line.
- **Option B (implement, progressive enhancement, ~30 lines, no-JS baseline unaffected):**
  - `lang/en/layout.php`: `'share' => 'Share', 'shareCopied' => 'Link copied.', 'shareUnavailable' => 'Sharing is not available in this browser.'`
  - `layouts/app.blade.php`: add a `data-share-app hidden` button next to the theme toggle (same pattern as `app-header__toggle`).
  - `resources/js/calculator.ts`: `initShare()` — unhide when `navigator.share` exists; on click `navigator.share({ title: document.title, url: location.origin })`, else `navigator.clipboard.writeText(location.origin)` + status text; mirror the theme toggle's aria pattern.
  - Do **not** port the Android share strings (they were malformed). Own copy required.

### P5 — Favicon (closes G5, optional)
Smallest correct step: add `public/favicon.svg` — reuse the khatam path from `layouts/app.blade.php:30-31`, with a `prefers-color-scheme` fill rule — and `<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">` in the layout head. Replace or delete the 0-byte `favicon.ico` **with owner approval** (it currently serves an empty 200).

### P6 — About attribution (closes G6, owner decision)
If wanted: `lang/en/about.php` → `'versionNote' => 'Version 1.0 · Migrated from the Android Zakat Gold Calculator',` plus one `<p>` in `about.blade.php` under the source link. Skip the personal copyright string unless the owner asks for it.

### P7 — Accept a leading `+` in numeric input (closes G7, nit — skip if not a goal)
`CalculateZakatRequest.php:48-49`: change both regexes to `'/^[+-]?(?:\d+(?:\.\d+)?|\.\d+)$/'` (verified `BigDecimal::of('+5')` → `5`). Add one case to `CalculateWebTest.php` ("accepts a leading plus sign"). Negative values remain blocked by `gt:0`.

### P8 — Documentation hygiene (closes FYI + G8 decision)
One short paragraph each, owner-approved, in `PRD.md` (or an ADR): (a) the Android reference ambiguity — shipped uruf model vs dead nisab-model classes, with the 100 KEPT @ 60 example; (b) `totalValue` intentionally not ported; (c) `welcome.blade.php` kept-or-deleted decision. This is what prevents a future audit from "restoring" the wrong model.

---

## 8. What was verified and is clean (no action)

- Formula, uruf table (85/200), rate, `≤ 0` semantics, rounding policy: exact parity with shipped Android.
- Golden values 1–3 and the exact-85 boundary: re-verified in a live browser session (not just tests).
- Precision, bounds, locale separators, error handling: web strictly better; documented as intentional in `PRD.md` §5.
- Copy: every *live* Android string has a web key, most verbatim; two deliberate rewordings (value label RM, about heading).
- Assets: nothing brand-specific existed in Android to migrate.
- API shape, AJAX/full-page fragment identity, no-JS baseline, both themes, focus management: verified.

## 9. Not verified (stated limits)

- Android side was reviewed statically; no Gradle build or Espresso run (no emulator in this environment).
- `npm run build` not executed (dev server used for QA); production bundle not re-built during this audit.
- Pint not run (no code modified).
