# Zakat Calculator

Estimate your Zakat obligation on gold holdings — a stateless, offline-friendly web calculator using the Malaysian `uruf` exemption model (85g for kept gold, 200g for worn gold, 2.5% rate).

## Quick Start

```bash
composer install
npm install
php artisan key:generate
./vendor/bin/sail up -d        # or php artisan serve for local dev without Docker
./vendor/bin/sail npm run dev  # or npm run dev
```

Open http://localhost. The calculator works without Docker (uses SQLite) — just set `DB_CONNECTION=sqlite` in `.env`.

## Features

- **Gold weight input** — grams, decimals accepted (dot or comma separators)
- **Gold category** — Kept (savings) or Worn (jewelry), driving the uruf exemption
- **Gold value per gram** — in your chosen currency
- **Currency selector** — MYR, USD, EUR, GBP, SGD, AED, INR, CAD, AUD
- **Result display** — uruf applied, weight minus uruf, payable value, Zakat due (2.5%)
- **Below uruf detection** — shows "Zakat not due" when holdings fall below the exemption
- **Light/dark theme** — follows OS, manual toggle, persisted in localStorage
- **About/Methodology page** — explains the calculation and disclaimers

## Tech Stack

| Layer | Choice |
|---|---|
| Language | PHP 8.5 |
| Framework | Laravel 13 |
| Frontend | Blade + Tailwind CSS v4 (CSS-first) + TypeScript + Vite 7 |
| Exact math | `brick/math` `BigDecimal` (no float for money/weight) |
| Testing | Pest v4 + PHPUnit 12 |
| Code style | Laravel Pint (PSR-12) |
| Static analysis | PHPStan level 6 (via Larastan) |

## Common Commands

| Task | Command |
|---|---|
| Run tests | `./vendor/bin/pest --compact` |
| Lint code style | `./vendor/bin/pint` |
| Fix code style | `./vendor/bin/pint --dirty --format agent` |
| Static analysis | `composer analyse` |
| Start dev server | `php artisan serve` |
| Tinker REPL | `php artisan tinker` |

## Architecture

See [ARCHITECTURE.md](ARCHITECTURE.md) for the full technical design. Summary:

- **Domain layer** (`app/Domain/Zakat/`) is pure PHP — no framework imports. Contains `ZakatCalculator` (single source of the formula), `ZakatInput`/`ZakatResult` (immutable value objects), `GoldCategory` (enum), `UrufRule`/`StandardUrufRule` (strategy).
- **HTTP layer** is thin — `CalculateZakatRequest` owns all validation and normalization; `CalculatorController` has 3 actions, each ≤ 3 statements.
- **Two entry points** for calculation: web POST `/calculate` (server-rendered) and API POST `/api/v1/calculate` (JSON). Both route through the same request class and domain call.
- **No persistence** — v1.0 is stateless between requests. Theme preference lives in browser `localStorage` only.
- **No network** — no live price feeds, no analytics, no third-party scripts.

## Testing

29 tests / 77 assertions covering golden values vs the Android reference, boundary cases (below uruf, exactly at uruf, zero, negative, overflow), JSON shape, and domain purity:

```
tests/
├── Unit/Domain/Zakat/ZakatCalculatorTest.php     # golden-value + boundary tests
├── Unit/Domain/Zakat/StandardUrufRuleTest.php     # uruf table tests
├── Feature/CalculateWebTest.php                  # web POST end-to-end
├── Feature/CalculateApiTest.php                  # API JSON shape + validation
└── tests/...                                     # Pest bootstrapping, Example tests
```

## Project Structure

```
app/
├── Domain/Zakat/            # pure-PHP domain (no framework imports)
│   ├── GoldCategory.php     # enum: KEPT (85g), WORN (200g)
│   ├── ZakatInput.php       # immutable input value object
│   ├── ZakatResult.php      # immutable result value object
│   ├── UrufRule.php         # interface: uruf grams by category
│   ├── StandardUrufRule.php # default impl: 85/200
│   └── ZakatCalculator.php  # the calculation engine (static)
├── Http/
│   ├── Controllers/CalculatorController.php  # index / calculate / about
│   └── Requests/CalculateZakatRequest.php    # all validation + normalization
resources/
├── views/calculator/index.blade.php   # calculator form + result
├── views/calculator/about.blade.php   # methodology + disclaimer
├── views/layouts/app.blade.php        # shared layout + theme toggle
└── lang/en.json                       # all user-facing strings
routes/
├── web.php      # GET /, POST /calculate, GET /about
└── api.php      # POST /api/v1/calculate
```

## Golden Values (from Android reference)

| Weight | Category | Value/gram | Result |
|---|---|---|---|
| 120g | Kept | 350.00 | uruf 35.00, payable 12,250.00, Zakat 306.25 |
| 50g | Kept | 350.00 | Below uruf, Zakat 0.00 |
| 210g | Worn | 350.00 | uruf 10.00, payable 3,500.00, Zakat 87.50 |

## License

MIT
