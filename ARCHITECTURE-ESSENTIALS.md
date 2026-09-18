# Zakat Calculator: Architecture Essentials

> **Fast context loader.** Read this file first. It contains only what cannot be ignored: rules, data structures, routes, and the stress-test audit. Full reasoning lives in `ARCHITECTURE.md`; product intent lives in `PRD.md`.

---

## 1. Non-Negotiable Rules

1. **Money and weight math uses `brick\math\BigDecimal` only.** Never `float`. Final money scale 2, `HALF_UP`.
2. **Domain (`app/Domain/Zakat/**`) is pure PHP.** No framework imports, no HTTP, no I/O. Its only dependency is `brick/math`.
3. **Validation lives only in `CalculateZakatRequest`.** Controllers never parse or validate manually.
4. **Controllers are thin:** coerce via the FormRequest, one domain call, render/return. The only allowed branch is the AJAX-vs-full-page render choice in `calculate`.
5. **v1.0 is stateless.** No persistence, no accounts, no analytics, no telemetry, no external calls. Theme preference lives in the browser `localStorage` only. The framework session carries nothing but CSRF + flashed validation errors/old input on the no-JS POST path.
6. **No network at calculation time.** No live gold prices, no third-party scripts.
7. **All user-facing strings centralized** in grouped language files `lang/en/*.php` (keys used as `__('calculator.…')`, `__('nav.…')`, …).
8. **Bounds enforced at the request boundary:** weight `(0, 1_000_000]`, value per gram `(0, 10_000_000]`. The domain additionally rejects non-positive weight/value in `ZakatInput`'s constructor (no `from()` factory exists).
9. **No new dependencies** without an entry in `ARCHITECTURE.md` (only approved one: `brick/math`, exact-pinned `0.19.1` in `require`).

---

## 2. Core Data Structures

```
ZakatInput    weightGrams: BigDecimal   (0 < w <= 1M)
              category:    GoldCategory (KEPT | WORN)
              valuePerGram:BigDecimal   (0 < v <= 10M)
              currencyCode:string       (ISO-4217, 3 chars)

GoldCategory  KEPT = uruf 85g   |   WORN = uruf 200g

UrufRule      urufFor(GoldCategory): BigDecimal   displayLabel(): string
StandardUrufRule = the 85/200 table

ZakatResult   urufGrams, weightMinusUruf, belowUruf: bool,
              payableValue, zakatDue, zakatRate (0.025), currencyCode
```

**Formula (single source: `ZakatCalculator`):**
```
weightMinusUruf = weight - uruf(category)
belowUruf       = weightMinusUruf <= 0
payableValue    = belowUruf ? 0 : weightMinusUruf * valuePerGram
zakatDue        = payableValue * 0.025   → scale 2, HALF_UP
```

**Reference goldens:** (120 KEPT @ 350) → minus 35.00, payable 12250.00, zakat 306.25. (50 KEPT @ 350) → below uruf, zakat 0. (210 WORN @ 350) → minus 10.00, payable 3500.00, zakat 87.50.

---

## 3. Primary Routes

| Method | URI | Action | Result |
|---|---|---|---|
| GET | `/` | `CalculatorController@index` | Form (empty state) |
| POST | `/calculate` | `CalculatorController@calculate` | Server-rendered result (no-JS safe) |
| GET | `/about` | `CalculatorController@about` | Methodology + disclaimer |
| POST | `/api/v1/calculate` | same request + domain | JSON: `{uruf_grams, weight_minus_uruf, below_uruf, payable_value, zakat_due, zakat_rate, currency}` |

---

## 4. Stress-Test Self-Audit

### 4.1 Breaking points under load and edge cases

- **Decimal-parsing ambiguity.** One parser path accepts both `.` and `,` separators. Breaking point: two users on opposite decimal conventions must both get the same number from the same string. Cover: normalization happens once in the FormRequest, before the domain sees it; unit + feature tests pin `"350.00"` and `"350,00"`.
- **Rounding boundary cases.** HALF_UP at scale 2 is applied only to the final `zakatDue` after full-precision multiplication. Breaking point: applying scale earlier changes results (e.g., `0.025 * 12250 = 306.25` exactly, but `0.025 * 3333.33` is `83.33325` → `83.33`). Cover: golden tests assert the exact decimal string, not a tolerance.
- **Weight minus uruf could be negative for valid inputs.** UI must render "Below uruf, Zakat not due" and a zero payable; it must never show a negative money amount. Cover: `belowUruf` flag drives the view branch; feature test asserts no negative displayed value.
- **The public POST routes are unauthenticated.** At scale, junk traffic costs CPU. Mitigation: the work is one exact multiplication (sub-millisecond); no side effects; rate limiting available on `api/v1/calculate` if it ever matters. Documented, not pre-configured (YAGNI).
- **CSS/theme drift.** Two themes equals four combos (theme × dense/spacious, theme × keyboard focus). Breaking point: a dark-mode-only bug that passes manual light-mode QA. Cover: design tokens as CSS custom properties; both themes in the manual QA checklist in `AGENTS.md`.

### 4.2 Unaccounted-for failure modes (identified during review)

- **Form double-submit** (double-click on Calculate) sends two identical POSTs. Stateless, idempotent, harmless; still, the JS enhancement should disable the button while in flight. Add to the JS acceptance criteria.
- **Browsers autofilling stale values** across visits can leave a "result" visible with inputs the user did not type. The index action always renders the fresh empty state; results are only ever shown for the request that produced them (no caching of results anywhere).
- **Unknown/bad currency codes** must fail validation (422) instead of falling through to a formatter error. The allowed-currency set lives in one constant shared by request and view.
- **Extremely long numeric strings** (e.g., 10+ digits) could exhaust decimal parsing. The `numeric` + `regex` + `max` rules in `CalculateZakatRequest` reject them at the boundary; the domain constructor re-checks positivity (maxima are a single-point request-boundary concern). No unbounded parse path exists in the domain.
- **`Intl.NumberFormat` differences between browsers** when formatting server-side vs client-side. Decision: the server renders canonical currency strings (server is the source of truth for display); the JS enhancement re-uses the server-rendered result and does not re-format money. Kills the whole class of drift.

### 4.3 Over-engineered components (identified for simplification)

- **`UrufRule` interface.** Future school presets are speculative. Kept because it is 2 methods, mirrors the proven Android seam, and costs nothing; flagged: if no second rule materializes by v1.2, delete the interface and read uruf straight from `GoldCategory`. Wiring today is direct instantiation in the controller (no container binding, deliberately deferred).
- **API route `/api/v1/calculate`.** Not strictly required by any PRD flow; it exists for testability and future clients. Simplification option: fold JSON responses into the web route. Kept separate because the versioned API is the stated standing convention and the cost is one route line.
- **Grouped `lang/en/*.php` files.** One language today. Kept because hard-coded strings are the single most expensive rework in Laravel and the files are trivial. (A duplicate `lang/en.json` with identical keys existed during implementation; it was removed as dead weight — the grouped PHP files are the single source.)
- **Do NOT build:** calculation history, local storage of results, export, config files for constants, auth scaffolding, a JS framework, or a design token build step beyond Tailwind v4 CSS-first variables.

---

## 5. GSTACK REVIEW REPORT

> **Status note (2026-09-18):** this report is the historical planning-phase record; it is retained unmodified for provenance. Post-implementation reconciliation corrected rules 4/5/7/8, the double-bounds claim, and the language-file path in this file set to match the shipped code.

Auto-review pipeline executed on this plan set (PRD.md + ARCHITECTURE.md + this file). SKILLS: autoplan (CEO, Design, DX, Eng phases), antislop (design filter), impeccable (UI direction). Environment note: gstack runtime preamble binaries are unavailable in this Windows CLI host; the autoplan methodology was applied directly with all decisions auto-resolved per the 6 principles, and the final approval gate was pre-empted by explicit user instruction to proceed autonomously.

| Phase | Scope checked | Outcome (auto-decided) |
|---|---|---|
| 1. CEO | Product completeness, persona coverage, scope vs Android fidelity | PRD kept the uruf model and preserved stateless/privacy constraints; sharpened currency as the web-only extension. Cut: accounts, history, price feeds (P1 completeness favors correctness of the core, not scope creep). |
| 2. Design | UI scope detected (form, result, about screens) | Mode Operate, dial ENERGY 1 / RHYTHM 1 / MOTION 1; tokens recorded in ARCHITECTURE.md section 9 with one-line reasons (R-31). Dark mode both themes, WCAG AA, 44px targets made requirements, not aspirations. |
| 2.5 DX | Developer-facing scope? | Not a developer tool and no agent-as-user. Declared NOT APPLICABLE with this justification; the single public JSON API is simple enough to be covered by the eng phase and feature tests. |
| 3. Eng | Data flow, decimal arithmetic, route surface, failure modes | Formula pinned to goldens; rounding policy fixed at final step only; idempotent stateless POSTs; stress-test audit above produced by this phase; interface kept with deprecation note (section 4.3). |

**Accepted obligations (eng phase):**
<!-- autoplan-accepted:eng -->
- All monetary math via brick/math BigDecimal, scale 2 HALF_UP only at final display. Verified by golden-value unit tests.
- Domain layer free of framework imports. Verified by a Pest arch test.
- POST /calculate and POST /api/v1/calculate fully covered by feature tests (valid, invalid, below-uruf, bad currency).
- No persistence, no analytics, no external network calls in v1.0. Verified by code review gate.
- Double-submit guarded in JS enhancement; server remains the money-formatting source of truth.
<!-- /autoplan-accepted:eng -->

**Rejected or deferred (with why):** calculation history (not in PRD), history persistence, live price integration (contradicts offline/privacy NFRs), a DESIGN.md full pass (deferred to UI implementation time; direction recorded here).

**Remaining taste decisions for the owner (non-blocking):** product name ("Zakat Calculator" inherited from the Android app; trivially changeable), accent color exact value (deep emerald proposed), whether the API route ships in v1.0 or waits for a client.