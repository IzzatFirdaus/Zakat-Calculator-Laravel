# Product: Zakat Calculator on Gold

<!-- impeccable:product-schema 1 -->

## Platform

Web — server-rendered (Blade) with Vite 7 + Tailwind CSS v4 progressive enhancement. No API consumers required in v1.0.

## Users

- Practicing Muslims (Sunni and Shia) seeking a quick, trustworthy Zakat estimate on gold holdings.
- Adults (18+) of any gender who hold gold savings or jewelry, primarily in Malaysia and other Muslim-majority markets.
- Users with low-to-moderate technical literacy who value simplicity over depth.
- Users on low-bandwidth or intermittent connections; the calculator works with no network dependency at calculation time.
- Solo use — single user per instance, not a team workspace.

## Product Purpose

A lightweight web application that helps Muslim users estimate their Zakat obligation on gold holdings using the Malaysian `uruf` exemption model (85g for kept/savings gold, 200g for worn/jewelry gold, 2.5% rate). The calculator provides transparent, auditable math with no accounts, no persistence, and no network dependency at calculation time — a direct web migration of the existing Android Zakat Gold Calculator.

## Positioning

A focused, trustworthy single-purpose tool: enter gold weight, category, and value; get Zakat due. No feature bloat, no login, no tracking.

## Operating Context

- Desktop-first primary use, responsive to mobile.
- Offline / low-bandwidth conditions are a real constraint; calculation must work without connectivity after assets are loaded.
- Laravel 13 + Blade, fronted by Vite 7 with Tailwind CSS v4 (CSS-first theme config) and TypeScript.
- Stateless: no accounts, no database writes from the calculator, no session usage for calculation data.
- Routes: `GET /` (calculator), `POST /calculate` (server-rendered result), `GET /about` (methodology), `POST /api/v1/calculate` (JSON API).

## Capabilities and Constraints

### In scope (v1.0)

- Gold weight input (grams, decimal, dot or comma separators)
- Gold category: Kept vs. Worn (drives 85g / 200g uruf exemption)
- Gold value per gram with currency selection (MYR default; USD, EUR, GBP, SGD, AED, INR, CAD, AUD supported)
- Calculation: uruf applied, weight minus uruf, payable value, Zakat due (2.5%)
- Below-uruf detection with clear "Zakat not due" messaging
- Light/dark theme toggle persisted in localStorage
- About / methodology page with disclaimer
- Server-rendered as the baseline (JS is enhancement only)

### Out of scope

- Cash, silver, business assets, debts, livestock, agricultural produce
- User accounts, cloud sync, multi-device history, persistence of past calculations
- Real-time or automated gold price feeds (manual entry only)
- Multi-language localization (English only in v1.0)
- Push or email notifications for Zakat due dates
- School-of-thought presets beyond standard uruf (future work)

## Brand Commitments

- Warm neutral palette with one deep emerald accent (trustworthy, Islamic tradition without gold/crescent cliche)
- System font stack, tabular numerals for money figures
- Minimal motion, maximum scanability for financial data
- No third-party brand dependencies (no icon libraries, no webfonts)

## Evidence on Hand

- Reference Android app (`D:\Projects\Zakat-Calculator`) provides the calculation model and golden test values.
- Domain arithmetic verified against 3 golden datasets with 29 passing tests.
- No user research, analytics, or feedback data beyond the reference implementation.

## Product Principles

1. **Correctness first** — calculation accuracy matches the reference Android implementation exactly. Golden values pin every output.
2. **Stateless by design** — no server-side persistence means no data loss, no sync conflicts, no session corruption.
3. **Progressive enhancement** — the server-rendered form and result path is the baseline. JavaScript adds fetch submission and theme toggle; it is never required.
4. **Simplicity over scope** — single-purpose calculator. Every feature must earn its place against "does this help the user compute Zakat correctly?"
5. **Privacy by default** — no analytics, no telemetry, no third-party trackers, no network calls at calculation time.

## Accessibility & Inclusion

- WCAG AA contrast floor (4.5:1 text, 3:1 large text)
- Minimum 44px tap targets on touch devices
- Full keyboard operability with visible focus states
- Both light and dark themes fully styled and tested
