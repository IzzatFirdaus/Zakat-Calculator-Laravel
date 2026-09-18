# Product: Zakat Calculator on Gold

<!-- impeccable:product-schema 1 -->

## Platform

Web — server-rendered (Blade) with Vite 7 + TypeScript progressive enhancement and hand-authored component CSS. No API consumers are required in v1.0.

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

- Desktop-first primary use, responsive to mobile (single-column down to 390px).
- Offline / low-bandwidth conditions are a real constraint; calculation must work without connectivity after assets are loaded.
- Laravel 13 + Blade, built with Vite 7 + TypeScript (esbuild; no separate typecheck step in the build).
- Styling is hand-authored component CSS: `resources/css/app.css` imports a tokens/base chain plus one file per surface (header, calculator, result, about, footer). Tailwind's import was removed during the 2026-09 frontend overhaul and its base normalization is vendored in `base.css`; the `tailwindcss` packages and the `@tailwindcss/vite` plugin remain installed but unused.
- Theme is a class on `<html>` (`html.dark`) mirrored in `data-theme` and persisted in `localStorage`.
- Stateless: no accounts, no database writes from the calculator, no session usage for calculation data.
- Routes: `GET /` (calculator), `POST /calculate` (server-rendered result; the same route returns the result partial for `X-Requested-With: XMLHttpRequest`), `GET /about` (methodology), `GET /language` (sets the locale preference cookie), `POST /api/v1/calculate` (JSON API).
- The JSON API exists as a symmetric output of the same domain call; it has no required consumers today.

## Capabilities and Constraints

### In scope

- Gold weight input (grams, decimal, dot or comma separators)
- Gold category: Kept vs. Worn (drives 85g / 200g uruf exemption)
- Gold value per gram with currency selection (MYR default; USD, EUR, GBP, SGD, AED, INR, CAD, AUD supported)
- Calculation: uruf applied, weight minus uruf, payable value, Zakat due (2.5%)
- Below-uruf detection with clear "Zakat not due" messaging
- Light/dark theme toggle persisted in localStorage
- About / methodology page with disclaimer
- Server-rendered as the baseline (JS is enhancement only)
- Result panel has five distinct states: empty (before submitting), result, below-uruf, per-field validation errors with a summary, and network/unexpected failure
- Zakatable-vs-uruf proportion meter on the result — presentational only, computed server-side; it never feeds the math
- Money figures are grouped and formatted by the server; the JSON API returns raw decimal strings so it stays locale-neutral
- Bilingual interface (English default, Malay) with a header language select next to the theme switcher; all user-facing strings live in `lang/en/*.php` and `lang/ms/*.php`, kept key-for-key in sync by a test
- Locale preference persists in an encrypted `locale` cookie; first visit auto-detects `ms` from `Accept-Language`. Money formatting and the JSON API stay locale-neutral
- v1.1 frontend overhaul (2026-09-18): result panel redesign, form affordances (unit/category/currency cues), inline error rendering, TypeScript module split. No change to the calculation, routes, or domain layer. Implemented in this release.
- v1.1 localization (2026-09-18): Malay copy + header language select + icon-only theme switcher. Implemented in this release.

### Binding constraints

- All money and weight arithmetic uses `brick\math\BigDecimal`; `float` is forbidden. Final scale 2 with half-up rounding, applied only at the last step.
- The formula lives in exactly one place (`ZakatCalculator::calculate()`); the web form and the JSON API route through the same request object and domain call. No per-channel forks.
- `app/Domain/Zakat/**` stays pure PHP — no framework imports, facades, HTTP, or I/O (enforced by an architecture test).
- Golden values from the Android reference must never change silently: 120g kept @ 350 → 35.00 / 12,250.00 / 306.25; 50g kept → below uruf, 0; 210g worn @ 350 → 10.00 / 3,500.00 / 87.50.
- The server is the single source of truth for money formatting; client JS renders server output and never re-formats.
- Locale changes copy only. Money stays `en`-grouped (`12,250.00`, Malaysian convention), the JSON API stays locale-neutral with English validation messages, and the domain layer stays locale-free.
- Language preference is the only cookie the calculator sets: first-party, encrypted, httponly, no tracking, no third party.
- No new dependencies and no new migrations without approval; the calculator stays stateless.

### Deliberately undecided

- Languages beyond English and Malay (e.g. Arabic)
- Native-speaker review of the Malay copy (see Evidence on Hand)
- Publish target, domain, and hosting

### Out of scope

- Cash, silver, business assets, debts, livestock, agricultural produce
- User accounts, cloud sync, multi-device history, persistence of past calculations
- Real-time or automated gold price feeds (manual entry only)
- Push or email notifications for Zakat due dates
- School-of-thought presets beyond standard uruf (future work)

## Brand Commitments

- Warm neutral palette with one deep emerald accent (trustworthy, Islamic tradition without gold/crescent cliche)
- System font stack, tabular numerals for money figures
- Minimal motion, maximum scanability for financial data
- No third-party brand dependencies (no icon libraries, no webfonts)

## Evidence on Hand

- Reference Android app (`D:\Projects\Zakat-Calculator`) provides the calculation model and the golden test values. It ships no Malay copy, so there was no source text to migrate.
- Verified by Pest: 80 passing tests / 272 assertions across domain arithmetic, the web form, the AJAX result partial, the JSON API shape, money formatting, locale resolution/cookie persistence, and `lang/en` ↔ `lang/ms` key parity. Golden datasets are pinned by tests, not by documentation.
- The Malay strings are agent-authored and machine-checked for key parity only; they have not been reviewed by a native speaker, and no religious authority has signed off on the fiqh terminology.
- Frontend QA evidence for both themes, both locales, and the 390px layout: `tasks/qa/` (screenshots from the 2026-09-18 overhaul and localization passes).
- Approved visual contract for the current interface: `tasks/mockups/a-refined-civic.html`; the settled design decisions are recorded in `ARCHITECTURE.md` section 9.
- Absences that future work must not invent: no publisher or institutional endorsement, no logo or brand asset, no user research, analytics, testimonials, customer names, benchmarks, pricing, or live gold-price source. The only authority statement is the About page disclaimer, which frames every output as an estimate to be confirmed with a trusted scholar.

## Product Principles

1. **Correctness first** — calculation accuracy matches the reference Android implementation exactly. Golden values pin every output.
2. **Stateless by design** — no server-side persistence means no data loss, no sync conflicts, no session corruption.
3. **Progressive enhancement** — the server-rendered form and result path is the baseline. JavaScript adds fetch submission, theme toggle, and instant language switching; the language control keeps a visible submit button until the change handler can carry the request, so it works with JS disabled. JS is never required.
4. **Simplicity over scope** — single-purpose calculator. Every feature must earn its place against "does this help the user compute Zakat correctly?"
5. **Privacy by default** — no analytics, no telemetry, no third-party trackers, no network calls at calculation time.

## Accessibility & Inclusion

- WCAG AA contrast floor (4.5:1 text, 3:1 large text)
- Minimum 44px tap targets on touch devices
- Full keyboard operability with visible focus states
- Both light and dark themes fully styled and tested
- `<html lang>` follows the selected locale, and the icon-only controls (theme switcher) keep an `aria-label` plus a visible focus ring
- `prefers-reduced-motion` respected (global override in `resources/css/base.css`)
- Every calculation path works with JavaScript disabled; the no-JS result page is an accessibility baseline, not a fallback afterthought
