# Zakat Calculator Frontend Overhaul Task List

## Phase 1: Foundation

- [x] Task 1: Repair localization and make copy assertions truthful
  - Acceptance: flat `lang/en.json`; all live strings resolve; tests assert literal rendered copy.
  - Verification: `vendor/bin/pest tests/Feature/CalculateWebTest.php`, `php artisan view:cache`, HTTP inspection.
  - Dependencies: None.
  - Files: `lang/en.json`, `tests/Feature/CalculateWebTest.php`.
  - Scope: Small.

- [x] Task 2: Establish the shared visual and semantic shell
  - Acceptance: semantic tokens, working light/dark themes, responsive header, skip link, no inline calculator styles.
  - Verification: `npm run build`, contrast checks, keyboard/mobile inspection.
  - Dependencies: Task 1.
  - Files: `resources/css/app.css`, `resources/views/layouts/app.blade.php`, `resources/js/calculator.ts`.
  - Scope: Medium.

### Checkpoint: Foundation complete

- [x] Translation keys resolve in live HTML.
- [x] Shared shell and theme behavior are stable.
- [x] Focused web tests pass.

## Phase 2: Core calculator flow

- [x] Task 3: Harden form validation and error UX
  - Acceptance: controlled validation for all edge cases; no-JS redirect path; AJAX 422 path; focusable summary; field associations.
  - Verification: focused web/API Pest tests, no-JS POST check, AJAX validation check.
  - Dependencies: Task 1.
  - Files: `app/Http/Requests/CalculateZakatRequest.php`, `resources/views/calculator/index.blade.php`, `resources/js/calculator.ts`, `tests/Feature/CalculateWebTest.php`.
  - Scope: Medium.

- [x] Task 4: Unify result rendering and progressive enhancement
  - Acceptance: one canonical result partial; complete SSR/AJAX fields; empty/loading/validation/network/success states; focus management; no client money formatting.
  - Verification: focused web tests, `npm run build`, valid/below-uruf/network-error manual flows.
  - Dependencies: Tasks 2 and 3.
  - Files: `resources/views/calculator/index.blade.php`, `resources/views/calculator/partials/result.blade.php`, `app/Http/Controllers/CalculatorController.php`, `resources/js/calculator.ts`, `resources/css/app.css`, `tests/Feature/CalculateWebTest.php`.
  - Scope: Medium.

### Checkpoint: Core flow complete

- [x] No-JS and AJAX validation paths both work.
- [x] Full-page and AJAX result markup are identical.
- [x] Valid, below-uruf, and error flows work end to end.

## Phase 3: Methodology and polish

- [x] Task 5: Rebuild the About page as a readable methodology surface
  - Acceptance: localized semantic methodology, real source link, prominent disclaimer, no-JS support.
  - Verification: focused web tests, `php artisan view:cache`, light/dark keyboard inspection.
  - Dependencies: Tasks 1 and 2.
  - Files: `resources/views/calculator/about.blade.php`, `lang/en.json`, `resources/css/app.css`, `tests/Feature/CalculateWebTest.php`.
  - Scope: Medium.

- [x] Task 6: Complete responsive, theme, and motion polish
  - Acceptance: correct at 320/768/1024/1440px; no overflow; 44px targets; visible focus; AA contrast; reduced motion; result remains focal point.
  - Verification: `npm run build`, contrast/overflow checks, responsive/theme click-through.
  - Dependencies: Tasks 2, 4, and 5.
  - Files: `resources/css/app.css`, `resources/views/layouts/app.blade.php`, `resources/views/calculator/index.blade.php`, `resources/views/calculator/about.blade.php`, `resources/js/calculator.ts`.
  - Scope: Medium.
  - Notes: contrast computed from tokens for 27 pairs x 2 themes (all pass AA; `border-strong` retuned to #7e8c81 / #6b7c71 for 3:1). 320px header overflow fixed by hiding the toggle's visible label below 40rem (aria-label retained, 44x44 target). Error-summary links now 45px tall. Result amount is now the largest text on the page (36px mobile / 60px desktop). All transitions are 120ms; the only animation is the 0.9s loading spinner, which `prefers-reduced-motion` neutralises (0.01ms, single iteration). Keyboard focus could not be exercised in the hidden browser (`document.hasFocus()` is false, so `:focus`/`:focus-visible` styles never apply) - the focus rules are asserted on the built CSS instead.

### Checkpoint: Ready for final QA

- [x] About page is semantic and localized.
- [x] Responsive, theme, keyboard, and reduced-motion checks pass.
- [x] No unresolved audit findings remain except explicitly documented tooling limits.

## Phase 4: Verification and handoff

- [x] Task 7: Run the automated audit and delivery gate
  - Acceptance: all routes and states exercised; no application console/log errors; anti-slop hard gates pass; Playwright limitation documented.
  - Verification: Laravel Boost browser logs, HTTP/DOM inspection, `npm run build`, manual click-through.
  - Dependencies: Tasks 1 through 6.
  - Files: None unless audit findings require a fix.
  - Scope: Small to medium.
  - Flows exercised: GET `/` and `/about` (HTTP 200, DOM + overflow probes at 320/768/1024/1440 in both themes); no-JS POST valid (200 with 306.25) and invalid (302 -> `/` with `data-validation-summary`, `aria-invalid="true"`, old input kept); AJAX success (306.25 MYR, 4 breakdown rows, focus on the result heading); AJAX 422 (summary focusable and placed before the form, previous result and input preserved, `aria-describedby` wired); below-uruf AJAX (status panel, no money amount, no negative money); reset link (returns to `/`, clears the form, restores the empty state, keeps the theme); theme toggle (dark/light, `aria-pressed`, localStorage, survives reload, OS preference on first visit).
  - Cleanliness: `storage/logs/browser.log` has 0 error/warning lines across 613 entries; `storage/logs/laravel.log` has no entries after 09:16 (all earlier errors are from this session's own dev iterations, none from the shipped code); the browser console's only "error" is the intentional 422 fetched by the AJAX client.
  - Audit fixes: theme toggle now ships `hidden` and is revealed by JS, so a no-JS visitor no longer gets a dead control; the About example copy lost its `→` glyphs (rewritten as prose, test updated).
  - Playwright limitation (exact reason): `playwright` is **not** a declared dependency - `package.json` has no entry (the copy under `node_modules` at 1.64.0-alpha-2026-09-14, with browsers cached in `%LOCALAPPDATA%/ms-playwright`, belongs to the host tooling, and using it was declined as out-of-constraint). Browser automation therefore ran through the in-app browser, which in this hidden session exposes no viewport resize, pointer, or keyboard input (`viewport 0x0`, `document.hasFocus() === false`). Responsive checks were done by loading each page in a same-origin iframe at 320/768/1024/1440px, where media queries evaluate against the iframe viewport; keyboard/focus behaviour is asserted from the built CSS (`:focus-visible` outline rules) rather than exercised.
  - Playwright command for when it is approved: `npm i -D @playwright/test && npx playwright install chromium && npx playwright test` against a spec covering `/`, `/about`, no-JS POST, AJAX success/422, below-uruf, theme toggle, reset, console-error capture, and Tab-order `:focus-visible` assertions.
  - Anti-slop Delivery Gate - Block 1 Hard Gate: R-02 PASS (glyph sweep of `app/ resources/ lang/ routes/` found no em/en dash in UI text; the only hit is the protected `SkillExecutor` CLI output). R-03 PASS (no overflow in any of the 16 width x theme probes). R-17/R-18/R-36/R-38 PASS (no statistics, testimonials, or security/compliance claims anywhere). R-23 PASS (only the khatam header mark and sun/moon glyphs, both documented in ARCHITECTURE.md section 9; no invented logo or photo). R-24 PASS (both nav links resolve to routes that return 200). R-25 PASS (27 token pairs x 2 themes computed; text floor 5.11:1, non-text floor 3.18:1). R-26 PASS (every control acts: form POSTs, reset navigates, nav and source links resolve, theme toggle is only rendered once JS can wire it). R-27 PASS (empty, loading, validation, network, unexpected and result states styled and covered by tests). R-28 PASS (no FAQ exists). R-32 PASS with the documented tooling limit (native controls, skip link, 3px `:focus-visible` outlines in the built CSS; not machine-exercised, see above). R-33 PASS (no runtime patching; all behaviour lives in `calculator.ts`). R-34 PASS (both themes complete, AA, and layout-identical at 320px). R-35 PASS (built and clicked through every interactive element). R-37 PASS (direction recorded in ARCHITECTURE.md section 9: Mode Operate, ENERGY 1 / RHYTHM 1 / MOTION 1).
  - Delivery Gate - Block 2 Purpose-Gate: PASS (no gradients, glows, glassmorphism, background patterns, capsule badges, illustrations, decorative button arrows, or shadow-on-everything; the single uppercase kicker "Details" is the section label and the inline SVGs are limited to controls plus the documented star mark).
  - Delivery Gate - Block 3 Liveliness: PASS (dials declared; output is quiet and numbers-first, matching them; the result amount is measurably the largest text per screen at 36px mobile / 60px desktop; whitespace is structural via `clamp()` section rhythm; one emerald accent on submit, focus, active theme, and the total row; identity motif is the eight-pointed star; Design Read written before generation).
  - Delivery Gate - Block 4 Craftsmanship and Quality Locks: PASS (no "AI default" justifications - each choice has a one-line reason in section 9; no dead or unlabelled controls; no filler sections, since every block is form, result, or methodology; resilience verified for no-JS, both themes, four widths, and reduced motion; palette is warm neutrals plus one accent, not a template; not a clone of a known product; CTA copy is product-specific: "Calculate Zakat", "Reset", "About").
  - Gate verdict: no failing item; nothing was shipped before the two findings above were fixed and re-verified.

- [x] Task 8: Final quality gates and review packet
  - Acceptance: focused/full tests, Pint, static analysis, build, and diff checks pass with baseline issues separated.
  - Verification: `vendor/bin/pest`, `vendor/bin/pint --dirty --format agent`, `composer analyse`, `npm run build`, `git diff --check`.
  - Dependencies: Task 7.
  - Files: `.gitignore` (one line, see below). No test changes were needed.
  - Scope: Small.
  - Tests: full suite `vendor/bin/pest` -> 48 passed (194 assertions) in 14.17s; focused `tests/Feature/CalculateWebTest.php` -> 25 passed (128 assertions).
  - Formatting: `vendor/bin/pint --dirty --format agent` -> passed (the repo has zero commits, so `--dirty` has no baseline to diff against; the touched paths were therefore also checked explicitly: `vendor/bin/pint --format agent app/Domain/Zakat app/Http routes lang tests` -> passed, and it did not touch `app/Services/` or `app/Console/Commands/ManageSkills.php`).
  - Static analysis: `composer analyse` -> 12 errors, all pre-existing in the protected skill-automation infra and untouched by this effort: `Services/SkillExecutor.php` (6: iterable types at 14/16/18/322, PHPDoc at 132, return type at 169), `Services/SkillRegistry.php` (4: 14 x2, 51, 54), `Console/Commands/ManageSkills.php` (2: 33, 55). Zero errors in `app/Domain/Zakat`, `app/Http`, `routes`, `lang`, or `tests` - no new errors were introduced.
  - Build: `npm run build` -> pass (app-CJIVMMvQ.css 70.27 kB / 14.69 kB gzip, app-BKrFJpma.js 54.90 kB / 20.63 kB gzip, 59 modules).
  - Diff/secrets: the repository has no commits yet (`git rev-parse HEAD` fails), so there is no diff to inspect; the equivalent checks are: `git diff --check` exits 0, `git check-ignore` confirms `.env`, `.env.testing`, `vendor`, `node_modules`, `public/build`, and both log files are ignored, and the only secret-like untracked path is `.env.example` (Laravel template, empty `APP_KEY`/AWS values, `DB_PASSWORD=password` placeholder - no real credentials).
  - Fix: `.playwright-mcp/` (28K of screenshot baselines and traces from prior browser tooling) was not ignored; a `.playwright-mcp/` line was added to `.gitignore` next to the existing `.playwright-cli/` entry so no generated assets can be staged. `.gitignore` is the only file touched by this task.

## Active constraints

- Do not change calculation semantics or golden values.
- Do not add runtime dependencies, persistence, analytics, or external network calls.
- Do not add Playwright without explicit approval.
- Keep server-rendered no-JS behavior as the baseline.
- Preserve the existing domain purity and thin-controller boundaries.
- No commits unless explicitly requested.