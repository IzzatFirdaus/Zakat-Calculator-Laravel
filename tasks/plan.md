# Implementation Plan: Zakat Calculator Frontend Overhaul

## Overview

Refactor the existing Laravel 13, Blade, Tailwind CSS v4, and TypeScript frontend into a production-ready, accessible, mobile-first calculator without changing the calculation model or adding runtime dependencies. The server-rendered POST flow remains the baseline. TypeScript provides progressive enhancement for AJAX submission, validation feedback, loading/error states, and theme switching.

This plan preserves the approved product direction in `ARCHITECTURE.md` section 9: Operate mode, ENERGY 1, RHYTHM 1, MOTION 1, warm neutrals, one deep-emerald accent, system fonts, tabular numerals, minimal motion, and a restrained eight-point geometric identity. No new frontend framework, state library, icon library, webfont, analytics, persistence, or network service is introduced.

## Current Audit Baseline

### Verified stack and constraints

- PHP 8.5, Laravel 13.32.0, SQLite.
- Blade SSR with Tailwind CSS v4, TypeScript, and Vite 7.
- `brick/math` 0.19.1 for exact decimal arithmetic.
- Pest 4.7.8 and Larastan/PHPStan.
- Stateless calculator with no database writes, accounts, analytics, or live price feeds.
- Domain layer is pure PHP and formula ownership remains in `ZakatCalculator::calculate()`.
- Supported currencies are defined in `CalculateZakatRequest::SUPPORTED_CURRENCIES`.

### Material findings to resolve

1. `lang/en.json` is nested, but Laravel JSON translations require flat keys. Live pages therefore render keys such as `calculator.title` instead of copy. Existing tests call `__('calculator.title')`, so they can pass while the rendered page is broken.
2. The full-page result and AJAX result partial have divergent markup and fields. The partial omits the applied-uruf row and uses invalid `<dt>`/`<dd>` children inside `<ol>`.
3. AJAX validation is not handled correctly. Laravel validation redirects the browser form path, while the fetch client replaces the result with a generic message and loses field-level errors.
4. The error summary is not focusable, field errors are not consistently associated with inputs, and invalid controls lack `aria-invalid`.
5. The loading state changes `aria-busy` and disables the button but has no visible loading state.
6. The About page contains hard-coded English copy, invalid description-list markup, inline styles, and a generic GitHub link. The Android reference identifies the real source as `https://github.com/IzzatFirdaus/Zakat-Calculator`.
7. The theme toggle can render the wrong initial `aria-pressed` state before TypeScript runs. The inline theme bootstrap should own the initial state.
8. Playwright is not installed and `playwright-cli` is unavailable. The audit can use Laravel Boost browser logs, HTTP/DOM inspection, and manual browser checks now. Adding Playwright requires a separate dev-dependency approval.
9. `composer analyse` has pre-existing errors in `app/Console/Commands/ManageSkills.php`; the plan must distinguish those from new errors.
10. The repository has no commits and all files are currently untracked. No commit or destructive Git action is in scope.

## Target Architecture

### 1. Server-rendered source of truth

- `GET /` renders the complete form and empty result state.
- `POST /calculate` validates through `CalculateZakatRequest`, calls the domain once, and renders the complete page.
- `POST /api/v1/calculate` keeps the existing JSON contract and exact decimal strings.
- `GET /about` renders the methodology and disclaimer.
- No calculation logic, money formatting, or validation moves into TypeScript.

### 2. Shared presentation structure

- Keep Blade as the rendering layer.
- Extract one canonical result partial used by both the full page and AJAX response.
- Use semantic headings, `<dl>` rows, status regions, and one result landmark.
- Keep the controller thin. Any presentation-only formatting stays outside the domain and does not duplicate the formula.
- Remove inline styles from calculator views.

### 3. Validation and state contract

- `CalculateZakatRequest` remains the only validation owner.
- Normalize one decimal separator consistently and reject malformed mixed separators.
- Add explicit user-facing validation messages from `lang/en.json`.
- Preserve the no-JS redirect behavior.
- For AJAX requests, return a structured 422 response that the client can render into the existing form without a page replacement.
- Keep old input values after validation failures.
- Focus the error summary and associate every field error with its control.

### 4. Progressive enhancement

- Keep `calculator.ts` dependency-free.
- Separate responsibilities with named functions: form submission, validation rendering, result replacement, loading state, network error state, and theme synchronization.
- Use data attributes as stable hooks.
- Disable double submission while a request is in flight.
- Render visible loading, success, validation, and network-error states.
- Never reformat money in the browser.
- Use the CSRF token from the existing meta tag.

### 5. Design system and interaction model

- Mobile-first single-column calculator.
- Desktop two-column form and result layout with the result panel visually secondary but always readable.
- One focal point per screen: the calculated Zakat amount or the below-uruf status.
- Warm neutral surfaces, near-black ink, deep-emerald primary actions and focus states, restrained semantic danger/success colors.
- System font stack with tabular numerals for all numeric output.
- Minimum 44px interactive targets, visible focus rings, keyboard-operable radio cards, and reduced-motion support.
- No gradients, glassmorphism, large shadows, generic icon sets, decorative badges, or page-load animation.
- Inline SVG only for the theme control and result/status indicators where it improves comprehension.

## Task List

### Phase 1: Foundation

#### Task 1: Repair localization and make copy assertions truthful

**Description:** Convert `lang/en.json` to flat Laravel translation keys, add all missing calculator, layout, About, validation, loading, and network-error strings, and update tests to assert rendered copy rather than unresolved translation keys.

**Acceptance criteria:**
- [ ] Every user-facing Blade and TypeScript string resolves to visible copy on live HTML responses.
- [ ] No nested JSON objects remain in `lang/en.json`.
- [ ] Web and API tests use literal expected strings for user-visible content.
- [ ] Missing or unresolved translation keys fail a focused test.

**Verification:**
- [ ] `vendor/bin/pest tests/Feature/CalculateWebTest.php`
- [ ] `php artisan view:cache`
- [ ] HTTP inspection of `/` and `/about`

**Dependencies:** None.

**Files likely touched:**
- `lang/en.json`
- `tests/Feature/CalculateWebTest.php`

**Estimated scope:** Small, 2 files.

#### Task 2: Establish the shared visual and semantic shell

**Description:** Refine the existing design tokens and shared layout, remove inline styling from the calculator shell, add a skip link and stable landmarks, and align header navigation/theme behavior with the approved visual direction.

**Acceptance criteria:**
- [ ] Light and dark themes use semantic tokens for every surface, text, border, focus, danger, and success state.
- [ ] Header navigation and theme control remain usable at 320px without overflow.
- [ ] Theme state is initialized before painted content and `aria-pressed` matches the active theme.
- [ ] Skip link, header, navigation, main, and footer semantics are valid.
- [ ] No calculator view contains inline styles.

**Verification:**
- [ ] `npm run build`
- [ ] Contrast checks for all token pairs
- [ ] Manual keyboard and mobile-width inspection

**Dependencies:** Task 1.

**Files likely touched:**
- `resources/css/app.css`
- `resources/views/layouts/app.blade.php`
- `resources/js/calculator.ts`

**Estimated scope:** Medium, 3 files.

### Phase 2: Core calculator flow

#### Task 3: Harden form validation and error UX

**Description:** Make the FormRequest normalization and messages explicit, preserve the server-rendered validation path, and implement a structured AJAX validation response with field-level rendering and focus management.

**Acceptance criteria:**
- [ ] Empty, zero, negative, malformed decimal, over-bound, invalid category, and unsupported currency inputs produce controlled validation responses.
- [ ] Dot and comma decimal inputs remain equivalent when valid.
- [ ] No-JS invalid submissions redirect with old input and visible field errors.
- [ ] AJAX invalid submissions return 422 JSON and preserve the entered form.
- [ ] Error summary is focusable and links to invalid fields.
- [ ] Invalid controls expose `aria-invalid="true"` and `aria-describedby` error IDs.

**Verification:**
- [x] `vendor/bin/pest tests/Feature/CalculateWebTest.php` (17 tests, 68 assertions)
- [x] `vendor/bin/pest tests/Feature/CalculateApiTest.php` (5 tests, 21 assertions)
- [ ] Manual no-JS POST check (verified via automated test)
- [ ] Manual AJAX validation check (verified via automated test)

**Dependencies:** Task 1.

**Files likely touched:**
- `app/Http/Requests/CalculateZakatRequest.php`
- `resources/views/calculator/index.blade.php`
- `resources/js/calculator.ts`
- `tests/Feature/CalculateWebTest.php`

**Estimated scope:** Medium, 4 files.

#### Task 4: Unify result rendering and progressive enhancement

**Description:** Replace divergent result markup with one canonical partial, render all result states consistently, and make the TypeScript client replace only the result region while preserving accessibility and server-rendered money output.

**Acceptance criteria:**
- [x] Full-page and AJAX results contain the same fields and semantic structure.
- [x] Applied uruf, weight minus uruf, payable value, and total Zakat are present for above-uruf results.
- [x] Below-uruf results show a clear status and zero payable value without negative money.
- [x] Empty, loading, validation, network-error, and success states are visually and semantically distinct.
- [x] Result updates move focus to the result heading or status without disrupting the form.
- [x] Browser code never calls currency formatting or recalculates money.

**Verification:**
- [x] `vendor/bin/pest tests/Feature/CalculateWebTest.php`
- [x] `npm run build`
- [x] Manual valid, below-uruf, and network-error flows

**Dependencies:** Tasks 2 and 3.

**Files likely touched:**
- `resources/views/calculator/index.blade.php`
- `resources/views/calculator/partials/result.blade.php`
- `app/Http/Controllers/CalculatorController.php`
- `resources/js/calculator.ts`
- `resources/css/app.css`
- `tests/Feature/CalculateWebTest.php`

**Estimated scope:** Medium, 6 files.

### Phase 3: Methodology and polish

#### Task 5: Rebuild the About page as a readable methodology surface

**Description:** Replace invalid markup and hard-coded copy with semantic, localized methodology content, a real source link, and a clear disclaimer while preserving the existing route and product facts.

**Acceptance criteria:**
- [x] All About copy comes from `lang/en.json`.
- [x] Methodology steps use valid semantic markup.
- [x] The source link points to the Android reference repository.
- [x] The disclaimer is prominent and readable in both themes.
- [x] The page works without JavaScript.

**Verification:**
- [x] `vendor/bin/pest tests/Feature/CalculateWebTest.php`
- [x] `php artisan view:cache`
- [x] Manual light/dark and keyboard inspection

**Dependencies:** Tasks 1 and 2.

**Files likely touched:**
- `resources/views/calculator/about.blade.php`
- `lang/en.json`
- `resources/css/app.css`
- `tests/Feature/CalculateWebTest.php`

**Estimated scope:** Medium, 4 files.

#### Task 6: Complete responsive, theme, and motion polish

**Description:** Apply the final mobile-first layout, spacing, typography, focus, status, and reduced-motion refinements across the calculator and About pages.

**Acceptance criteria:**
- [x] Layouts are correct at 320px, 768px, 1024px, and 1440px.
- [x] No horizontal overflow or clipped text exists at inspected widths.
- [x] Every interactive element has a 44px minimum target and visible focus state.
- [x] Light and dark themes pass WCAG AA contrast checks.
- [x] Motion is limited to state transitions under 150ms and respects `prefers-reduced-motion`.
- [x] The result amount remains the primary visual focal point.

**Verification:**
- [x] `npm run build`
- [x] Contrast and overflow checks
- [x] Manual responsive and theme click-through

**Dependencies:** Tasks 2, 4, and 5.

**Files likely touched:**
- `resources/css/app.css`
- `resources/views/layouts/app.blade.php`
- `resources/views/calculator/index.blade.php`
- `resources/views/calculator/about.blade.php`
- `resources/js/calculator.ts`

**Estimated scope:** Medium, 5 files.

### Phase 4: Verification and handoff

#### Task 7: Run the automated audit and delivery gate

**Description:** Re-run the frontend audit using available MCP/browser tooling, verify all user flows, and record any remaining limitations. Playwright is not currently installed, so browser automation is an explicit tooling decision rather than a silent omission.

**Acceptance criteria:**
- [x] GET `/`, GET `/about`, no-JS POST, AJAX success, AJAX validation, below-uruf, theme toggle, and reset flows are exercised.
- [x] Browser console and Laravel logs contain no application errors.
- [x] Empty, loading, error, and result states are verified in both themes.
- [x] Any unavailable Playwright coverage is listed with the exact reason and proposed command.
- [x] Anti-slop Delivery Gate has no failing hard-gate item.

**Verification:**
- [x] Laravel Boost browser logs
- [x] HTTP/DOM inspection
- [x] `npm run build`
- [x] Manual click-through

**Dependencies:** Tasks 1 through 6.

**Files likely touched:** None unless audit findings require a fix.

**Estimated scope:** Small to medium.

#### Task 8: Final quality gates and review packet

**Description:** Run the repository's narrow and full quality checks, separate pre-existing static-analysis issues from new issues, inspect the final diff, and prepare the implementation summary.

**Acceptance criteria:**
- [x] Focused Pest tests pass.
- [x] Full Pest suite passes, or pre-existing failures are clearly identified.
- [x] `vendor/bin/pint --dirty --format agent` completes successfully.
- [x] `composer analyse` reports zero new errors; pre-existing `ManageSkills.php` errors are documented separately.
- [x] `npm run build` passes.
- [x] Final Git diff contains only intended files and no secrets or generated assets.

**Verification:**
- [x] `vendor/bin/pest`
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `composer analyse`
- [x] `npm run build`
- [x] `git diff --check`

**Dependencies:** Task 7.

**Files likely touched:** Tests only if a verification gap is found.

**Estimated scope:** Small.

## Checkpoints

### Checkpoint: Foundation complete

- [x] Translation keys resolve in live HTML.
- [x] Shared shell and theme behavior are stable.
- [x] Focused web tests pass.

### Checkpoint: Core flow complete

- [x] No-JS and AJAX validation paths both work (tests verify).
- [x] Full-page and AJAX result markup are identical (Task 4).
- [x] Valid, below-uruf, and error flows work end to end (Task 4).

### Checkpoint: Ready for final QA

- [x] About page is semantic and localized.
- [x] Responsive, theme, keyboard, and reduced-motion checks pass.
- [x] No unresolved audit findings remain except explicitly documented tooling limits.

## Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Nested translations silently render keys | High | Flatten JSON and add live-copy assertions before UI work. |
| AJAX validation redirects or loses field errors | High | Define a structured 422 contract and render errors into the existing form. |
| Result markup diverges between SSR and AJAX | High | Use one canonical result partial for both paths. |
| Playwright is unavailable | Medium | Use Laravel Boost browser logs, HTTP/DOM checks, and manual browser QA now; request approval before adding a dev dependency. |
| Static analysis baseline is dirty | Medium | Record baseline errors and fail only on new Larastan errors. |
| Over-refactoring a stateless v1.0 product | Medium | Keep Blade SSR, dependency-free TS, and the existing domain boundary. |
| Currency presentation expectations are ambiguous | Medium | Preserve the existing server-rendered canonical code plus two-decimal output unless locale-aware symbols are explicitly approved. |

## Open Decisions

1. **Playwright coverage:** Playwright is not installed. Do not add it during implementation without approval. If automated browser tests are required, add a dev-only Playwright dependency and a focused test script as a separate approved task.
2. **Currency display:** The current architecture specifies server-rendered canonical values such as `306.25 MYR`. Keep that contract for this overhaul unless locale-aware symbols and grouping are explicitly requested.
3. **Design documentation:** The approved visual direction already lives in `ARCHITECTURE.md` section 9. Do not create a new `DESIGN.md` unless the user explicitly requests one.

## Definition of Done

- Calculation semantics and golden values are unchanged.
- The server remains the source of truth for validation, calculation, and money output.
- The no-JS path remains fully functional.
- AJAX adds visible loading, validation, success, and network-error states.
- All user-facing strings are centralized and resolve correctly.
- Both themes, all inspected breakpoints, keyboard navigation, focus management, and reduced-motion behavior are verified.
- No new runtime dependency, persistence, analytics, or external network call is introduced.
- Focused tests, build, Pint, and static analysis checks pass with pre-existing issues clearly separated.
