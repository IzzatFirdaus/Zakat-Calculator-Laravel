# Zakat Calculator: Architecture Document

> **Status:** v1.0, migrated from the Android application (`D:\Projects\Zakat-Calculator`). Reconciled against the implemented codebase on 2026-09-18.
> **Single source of truth** for technical design. Translates `PRD.md` into concrete engineering decisions. All environments, routes, data structures, and non-negotiable rules live here.

---

## 1. Tech Stack

| Layer | Choice | Rationale |
|---|---|---|
| **Language** | PHP 8.5 (installed); `composer.json` requires `^8.3` | Confirmed installed (`php -v`): 8.5. |
| **Framework** | Laravel 13.32 (installed) | Existing repository; no framework change. |
| **Frontend** | Blade templates + Tailwind CSS v4 (CSS-first) + TypeScript + Vite 7 | Already configured in this repository (`vite.config.js`, `package.json`). Zero new frontend dependencies. |
| **Exact decimal math** | `brick/math` `BigDecimal` (exact pin `0.19.1` in `composer.json` `require`) | Money and weight math must be exact. |
| **Validation** | Laravel FormRequest | All input validation in one class, never in controllers. |
| **Testing** | Pest v4 + PHPUnit 12 (installed) | Existing stack. |
| **Static analysis** | Larastan (PHPStan level 6, installed) | Existing `phpstan.neon`. |
| **Code style** | Laravel Pint (installed) | PSR-12 baseline. |
| **Database** | None used in v1.0 | The product is stateless (see section 4). Default Laravel schema (`users`, `cache`, `jobs`) remains untouched. |
| **Auth** | None | No accounts in v1.0. |
| **Network** | None at calculation time | No live price APIs, no analytics, no third-party scripts. |
| **HTTP server** | `artisan serve` (dev), any PHP 8.5 FPM host (prod) | No special platform requirements. |

### Dependency decisions (approved by this migration task)

- `brick/math` `0.19.1` is declared as a direct dependency (exact pin) in `composer.json` `require`. No other new packages.
- No additional npm packages. Theme and number handling use platform features (CSS custom properties, `localStorage`).

---

## 2. Project Structure

```
app/
├── Console/Commands/ManageSkills.php        # existing (skill automation, untouched)
├── Domain/Zakat/                            # NEW: pure-PHP domain (no framework imports)
│   ├── GoldCategory.php                     # enum: KEPT (85g), WORN (200g)
│   ├── ZakatInput.php                       # immutable value object (weight, category, value, currency)
│   ├── ZakatResult.php                      # immutable value object (all outputs)
│   ├── UrufRule.php                         # interface: uruf grams by category + display label
│   ├── StandardUrufRule.php                 # default impl: KEPT=85, WORN=200
│   └── ZakatCalculator.php                  # pure service: the calculation engine
├── Http/
│   ├── Controllers/CalculatorController.php # index / calculate / about (thin)
│   ├── Controllers/LanguageController.php   # GET /language: sets the locale cookie, redirects back
│   ├── Middleware/SetLocale.php             # web locale: cookie > Accept-Language > app.locale
│   ├── Requests/CalculateZakatRequest.php   # all validation for /calculate
│   └── Controller.php                       # existing base
├── Models/                                  # existing (User only)
├── Providers/                               # existing
└── Services/                                # existing skill automation (untouched)

resources/
├── css/app.css                              # existing Tailwind v4 entry; design tokens live here
├── js/app.ts                                # existing entry (unchanged)
├── js/calculator.ts                         # NEW: progressive enhancement (fetch + theme toggle)
├── js/bootstrap.ts                          # existing
└── views/
    ├── layouts/app.blade.php                # NEW: shared layout (header, theme toggle, footer)
    └── calculator/
        ├── index.blade.php                  # NEW: calculator screen (form + result)
        ├── about.blade.php                  # NEW: methodology / disclaimer screen
        └── partials/result.blade.php        # NEW: result partial reused by full page and AJAX fragment

lang/
├── en/                                      # NEW: all user-facing strings, grouped PHP files
│   ├── about.php  app.php  calculator.php  footer.php  layout.php  meta.php  nav.php
└── ms/                                      # Malay: same keys (agent-authored, not native-reviewed)
    └── about.php  app.php  calculator.php  footer.php  layout.php  meta.php  nav.php

routes/
├── web.php                                  # GET /, POST /calculate, GET /about, GET /language
└── api.php                                  # POST api/v1/calculate (JSON)

tests/
├── Unit/Domain/Zakat/ZakatCalculatorTest.php # golden-value tests vs Android reference
├── Unit/Domain/Zakat/StandardUrufRuleTest.php
├── Unit/Domain/Zakat/ArchTest.php            # enforces domain purity (no framework imports)
├── Feature/CalculateWebTest.php              # POST /calculate end to end
├── Feature/CalculateApiTest.php              # POST api/v1/calculate (JSON shape)
├── Feature/SmokeTest.php                     # page loads, asset wiring
├── Feature/LocalizationTest.php              # locale resolution, cookie, lang key parity, icon-only toggle
└── (existing Pest.php, TestCase.php, Example tests, SkillAutomationTest)
```

No new database migrations. No new config files (constants live in the domain, section 5). The stock `resources/views/welcome.blade.php` scaffold was deleted in the reconciliation pass; the calculator never routed to it.

---

## 3. Data Models (Domain)

All monetary and weight values use `brick\math\BigDecimal`. Money output scale is `2`, `RoundingMode::HALF_UP`.

### 3.1 `GoldCategory` (enum)

| Case | Meaning | Uruf exemption (grams) |
|---|---|---|
| `KEPT` | Gold held as savings / investment | 85 |
| `WORN` | Gold worn as personal jewelry | 200 |

### 3.2 `ZakatInput` (immutable)

| Field | Type | Constraints |
|---|---|---|
| `weightGrams` | `BigDecimal` | `> 0`, `<= 1_000_000` |
| `category` | `GoldCategory` | `KEPT` or `WORN` |
| `valuePerGram` | `BigDecimal` | `> 0`, `<= 10_000_000` |
| `currencyCode` | `string` | ISO-4217, 3 uppercase letters, in supported set |

The constructor `new ZakatInput(...)` enforces positivity bounds and throws `InvalidArgumentException` on violation. The domain never trusts a raw request payload; the FormRequest guards the boundary first.

### 3.3 `ZakatResult` (immutable)

| Field | Type | Notes |
|---|---|---|
| `urufGrams` | `BigDecimal` | Applied exemption for the chosen category |
| `weightMinusUruf` | `BigDecimal` | `weight - urufGrams` (may be negative/zero) |
| `belowUruf` | `bool` | `weightMinusUruf <= 0` |
| `payableValue` | `BigDecimal` | `belowUruf ? 0 : weightMinusUruf * valuePerGram` |
| `zakatDue` | `BigDecimal` | `payableValue * ZAKAT_RATE`, rounded HALF_UP at scale 2 |
| `zakatRate` | `BigDecimal` | constant `0.025` |
| `currencyCode` | `string` | echoed from input for formatting |

### 3.4 No persistence layer

v1.0 is stateless between requests in the product sense: the calculator stores nothing to the database, cache, or any external service, and the only persisted user preference is the theme in the browser's `localStorage`. The Laravel framework session (file driver) is still used by the `web` middleware group for two ephemeral purposes only: the CSRF token, and flashing validation errors + old input on the no-JS POST path. That is request-scope plumbing, not product state, and nothing is tracked across visits.

---

## 4. Calculation Engine

### 4.1 `ZakatCalculator`

Pure function: `calculate(ZakatInput $input, UrufRule $rule): ZakatResult`. No framework imports; fully unit-testable.

```
urufGrams       = rule.urufFor(input.category)          # 85 (KEPT) | 200 (WORN)
weightMinusUruf = input.weightGrams - urufGrams         # exact decimal
belowUruf       = weightMinusUruf <= 0
payableValue    = belowUruf ? 0 : weightMinusUruf * input.valuePerGram
zakatDue        = (payableValue * ZAKAT_RATE).toScale(2, HALF_UP)
```

- `ZAKAT_RATE = new BigDecimal('0.025')` (2.5%).
- Multiplication keeps full precision; only the final monetary display value is scaled to 2 with HALF_UP. `weightMinusUruf` is scaled to 2 at serialization too, matching the Android `%.2f` display.

### 4.2 `UrufRule` interface and `StandardUrufRule`

`UrufRule` exposes `urufFor(GoldCategory $category): BigDecimal` and `displayLabel(): string`. `StandardUrufRule` returns the documented 85/200 table. The strategy seam exists so future school-of-thought presets (Hanafi, Shafi'i) can be added without touching the calculator. Current wiring is direct instantiation: `CalculatorController` constructs `new StandardUrufRule(...)` where needed; no container binding is registered (deliberately deferred until a second rule exists).

### 4.3 Migration fidelity

The golden dataset in section 8 locks the exact Android arithmetic. Reference examples:

- (120g, KEPT, 350.00): uruf 85, minus uruf 35.00, payable 12250.00, zakat 306.25
- (50g, KEPT, 350.00): below uruf, payable 0.00, zakat 0.00
- (210g, WORN, 350.00): uruf 200, minus uruf 10.00, payable 3500.00, zakat 87.50

---

## 5. System Components and Data Flow

```
Browser
  │  GET /
  ▼
routes/web.php ──► CalculatorController@index ──► view: calculator/index (form, empty state)
                                                  │
  POST /calculate (form, CSRF)                    │
  ▼                                              ▼
CalculateZakatRequest (validate + normalize) ──► ZakatCalculator (domain) ──► view: calculator/index + result
  │                                                  ▲
  └─ invalid: redirect back with errors ─────────────┘
                                                  │
  POST api/v1/calculate (Accept: application/json)
  ▼
CalculateZakatRequest ──► ZakatCalculator ──► JSON: { uruf_grams, weight_minus_uruf,
                                                     below_uruf, payable_value,
                                                     zakat_due, zakat_rate, currency }
```

Component responsibilities:

| Component | Responsibility |
|---|---|
| `CalculatorController` | Thin actions: `index` (form + currency list), `calculate` (one domain call; renders the result partial for AJAX or the full page otherwise), `about`, `apiCalculate` (JSON). Never computes, never validates manually. |
| `CalculateZakatRequest` | Whitelist + normalize + validate `weight` (≤ 1M), `value` (≤ 10M), `category` (`kept`/`worn`), `currency` (ISO-4217 set). Allowed currency set is a constant (`SUPPORTED_CURRENCIES`). Exposes `toInput()` to build the domain value object. |
| `ZakatCalculator` | The only place the formula lives. |
| `calculator.ts` | Progressive enhancement: submit via `fetch`, render result in place, toggle theme into `localStorage`. The page works fully without JS. |
| Layout + views | All strings read from grouped translation keys backed by `lang/{en,ms}/*.php`. |

### ASCII dependency graph (review artifact)

```
CalculatorController ──► CalculateZakatRequest
        │
        └──► ZakatCalculator ──► UrufRule (interface)
                        │              ▲
                        │              └──► StandardUrufRule ──► GoldCategory
                        └──► ZakatInput        ZakatResult
```

Dependencies point inward: controllers depend on the domain; the domain depends on nothing outside `brick/math` and PHP.

---

## 6. Routes

### 6.1 Web routes (`routes/web.php`)

| Method | URI | Action | Notes |
|---|---|---|---|
| `GET` | `/` | `CalculatorController@index` | Calculator screen (empty state). |
| `POST` | `/calculate` | `CalculatorController@calculate` | Validates, computes, renders result. No-JS compatible. |
| `GET` | `/about` | `CalculatorController@about` | Methodology and disclaimer. |
| `GET` | `/language` | `LanguageController@update` | `?locale=en\|ms` sets the `locale` cookie (`cookie()->forever`, encrypted, httponly, samesite=lax) and redirects back; unsupported values fall back to the current locale. A plain GET form, so it works with JS disabled. |

All `POST` routes use the default `web` middleware group (session, CSRF). `SetLocale` is appended to that group (`bootstrap/app.php`), so the API routes keep `app.locale` and return English messages.

### 6.2 API routes (`routes/api.php`)

| Method | URI | Notes |
|---|---|---|
| `POST` | `/api/v1/calculate` | JSON in, JSON out. Same `CalculateZakatRequest`. Response uses stable snake_case keys (section 5). |

Version prefix `/api/v1` is the standing convention for any future API surface.

---

## 7. Environment Configuration

| Item | Value |
|---|---|
| New env variables | **None.** The app reads no secrets, keys, or feature flags. |
| `config/` changes | **None.** Domain constants are code, not config. |
| `.env` | Unchanged from scaffold, except `APP_NAME="Zakat Calculator"` (display only, no logic depends on it). |
| Locale | `APP_LOCALE=en`, `APP_FALLBACK_LOCALE=en`. The web locale is resolved per request by `SetLocale`: `locale` cookie → `Accept-Language` (matched against `en`, `ms`) → `APP_LOCALE`. The fallback locale makes a missing Malay key render English rather than leak a raw key. |
| Databases | Local dev SQLite (`DB_CONNECTION=sqlite`, `database/database.sqlite`) exists only because the default Laravel scaffold ships that way; the calculator never queries it. Tests use in-memory SQLite via `.env.testing` / `phpunit.xml`. No domain tables. |
| Session/cache | `SESSION_DRIVER=file` and `CACHE_STORE=file` are required framework plumbing for CSRF and validation-error flashing on the web POST path (see section 3.4). No product state is stored. |

---

## 8. Testing Strategy

### 8.1 Unit: `ZakatCalculatorTest`

Parameterized golden tests against the Android reference dataset (section 4.3), plus:
- Below-uruf and exactly-at-uruf boundary (payable 0).
- Decimal weights and values keep precision until the final HALF_UP scale-2 rounding.
- Large-but-valid inputs (1,000,000 g) do not error and format cleanly.
- `ZakatInput`'s constructor rejects zero/negative weight and value with `InvalidArgumentException`; over-bound and unknown-currency inputs are rejected at the request boundary (`CalculateZakatRequest`), which is the single enforcement point for maxima.

### 8.2 Unit: `StandardUrufRuleTest`

`urufFor(KEPT)` = 85, `urufFor(WORN)` = 200, label present.

### 8.3 Feature: `CalculateWebTest`

- Valid POST renders the result (weight minus uruf, payable, zakat).
- Invalid POST redirects back with field errors; no 500.
- GET `/` renders the form; GET `/about` renders methodology.

### 8.4 Feature: `CalculateApiTest`

- Valid POST returns 200 with exact JSON keys and values.
- Invalid POST returns 422 with validation errors.
- Unknown currency returns 422.

### 8.5 Arch test (Pest)

- `app/Domain/Zakat/**` must not import from any `Illuminate\*` or framework namespace (purity rule).

### 8.6 Feature: `LocalizationTest`

- Every `lang/en/*.php` key exists in `lang/ms/*.php` (recursive key-path parity over the seven groups).
- `GET /language?locale=ms` sets the cookie and redirects back to the referring page; unknown/unsupported values fall back; a missing cookie auto-detects `ms` from `Accept-Language`; the cookie wins over the header.
- Malay strings render on the page, in the AJAX result partial (with money formatting unchanged), and in validation errors; the JSON API stays English.
- The theme switcher button contains no text node (icons only, `aria-label` retained).

### 8.7 Quality gates (every change)

`composer fix && composer analyse && composer test` must pass; zero new Larastan errors; Pint clean.

---

## 9. Design System (design-phase decision record)

Mode: **Operate** (user completes a task: compute Zakat, read the answer). Scanability and correctness of numbers outrank expression.

Reading this as: a financial utility for a general audience, government-service clarity, dial **ENERGY 1 / RHYTHM 1 / MOTION 1**.

| Token group | Decision | One-line reason |
|---|---|---|
| Palette | Warm neutral background (stone family), near-black ink text, one deep emerald accent | Green reads trustworthy and connects to the Islamic tradition without the gold/crescent cliche; single accent keeps the money numbers dominant. |
| Accent use | Submit button, focus rings, active theme state only | One deliberate accent; the result figures are the focal point, not decoration (R-31). |
| Typography | System UI font stack (no webfont download), `font-variant-numeric: tabular-nums` for all money figures | No network dependency at render; tabular figures keep columns of money aligned. |
| Radius | Small, consistent token (one value, never pills) | Quiet, precise calculator feel; pills would fight the numeric focus (R-11). |
| Motion | Hover/focus transitions only, < 150ms | Nothing animates the math; motion must never delay reading the answer (R-19). |
| Dark mode | Full-fidelity via CSS custom properties, follows OS by default, manual toggle, persisted in localStorage | Both themes must be complete and correct (R-34, R-21); manual override is cheap and expected. |
| Identity motif | Eight-pointed star (khatam) geometry as the header mark and a thin divider, used sparingly | A quiet Islamic-geometric identity that is specific to the product and not a stock icon (R-20). |
| Icons | Minimal inline SVGs only where a control needs one (theme toggle, reset) | No icon library dependency; fewer assets, faster page (R-04). |
| States | Empty, loading, error, and result states all styled explicitly | A form that lacks error/empty states is not finished (R-27). |
| Meter | Pure-CSS bar; Blade computes `--meter-width` / `--meter-tick` from `BigDecimal`; no animation | The zakatable-vs-uruf split is visible at a glance; geometry is presentation (it never feeds back into the math) and stays server-rendered for the no-JS path. |
| Money display | `App\Support\MoneyFormatter` (grouped, locale-pinned `en`) in views only; API responses stay raw strings | Server owns formatting so JS never re-formats money; the API stays locale-neutral for future consumers. |
| CSS architecture | Token / base / component partial chain under `resources/css/`, imported by `app.css`; Tailwind import removed; dependency packages untouched | Per-component files keep the cascade readable; the vendored preflight parity block preserves baseline normalization without the framework import. |

Contrast floor: WCAG AA (4.5:1 text, 3:1 large text). Tap targets >= 44px. All interactive elements keyboard-operable with visible focus (R-03, R-25, R-32). Full design direction gets validated against a written `DESIGN.md` when UI implementation starts.

---

## 10. Non-Negotiable Engineering Rules

1. **All money and weight math uses `brick\math\BigDecimal`.** `float`/`double` is forbidden for currency and weight arithmetic. Final money scaling is scale 2, `HALF_UP`.
2. **The domain layer (`app/Domain/Zakat/**`) is pure PHP.** No framework imports, no facades, no HTTP, no I/O. Testability is the gate.
3. **Validation lives only in `CalculateZakatRequest`.** Controllers never validate or parse manually.
4. **Controllers are thin.** Each action coerces input via the FormRequest, makes one domain call, and renders/returns; the only branching allowed is the AJAX-vs-full-page render choice in `calculate`. No arithmetic in controllers, ever.
5. **No persistence, no accounts, no analytics, no telemetry** in v1.0. The calculator never writes to the database, cache, or any external service. The framework session carries only CSRF + flashed validation errors/old input on the no-JS path (section 3.4).
6. **No network at calculation time.** No live gold price APIs, no third-party scripts, no tracking pixels.
7. **All user-facing strings are centralized** in grouped language files (`lang/en/*.php` plus the Malay `lang/ms/*.php`, kept key-for-key in sync by a test). No hard-coded UI text in Blade or JS. Locale affects text only: `app/Domain/Zakat/**` stays locale-free, money stays formatted by `MoneyFormatter` with `en` grouping (Malaysian convention), and the JSON API stays locale-neutral.
8. **Input bounds are enforced at the request boundary** (weight `<= 1_000_000 g`, value per gram `<= 10_000_000`); the domain additionally guards positivity in `ZakatInput`'s constructor.
9. **Every POST route is CSRF-protected and validated.** Never trust client-side values; the JS enhancement is cosmetic, not a security boundary.
10. **No new dependencies** without an architecture review entry in this document.

---

## 11. Risks and Mitigations

| Risk | Mitigation |
|---|---|
| Religious or jurisdictional accuracy disputes | Disclaimers on both screens; methodology page; pluggable `UrufRule` for future school presets; docstring references the source project. |
| Locale parsing bugs (comma vs dot decimals) | Normalization in `CalculateZakatRequest` (accepts both separators, one canonical path), covered by feature tests. |
| Decimal rounding drift vs the Android reference | Golden-value unit tests pin exact outputs before any refactor. |
| Form tampering with JS disabled | Server-rendered result path is the baseline; JS is enhancement only. |
| CSS/dark-mode drift | Design tokens in `app.css` custom properties; both themes covered by manual QA checklist in `AGENTS.md`. |
| DDoS/abuse of the public POST route | Stateless, CPU-bound single multiplication; no side effects; standard Laravel rate limiting available on the API route if needed (documented, not pre-configured). |