# Zakat Calculator: Product Requirements Document (PRD)

> **Status:** v1.0 scope, migrated from the Android application (`D:\Projects\Zakat-Calculator`). Reconciled against the implemented codebase on 2026-09-18.
> **Scope:** This document defines *what* to build and *why*. Technical implementation details are intentionally excluded and live in `ARCHITECTURE.md`.

---

## 1. Product Overview

### 1.1 Core Purpose

A lightweight **web application** that helps Muslim users estimate their **Zakat obligation on gold holdings**. The app performs transparent, auditable calculations using the user's gold weight, the gold category (kept vs. worn), and the current gold market value. It follows the widely used Malaysian `uruf` exemption model: 85 grams for gold kept as savings, 200 grams for gold worn as jewelry.

### 1.2 Migration Context

This product is a direct web migration of an existing Android app. All v1.0 behavior must remain faithful to the original calculation model, which was validated and published. The web version adds two extensions the platform enables: currency selection (the Android app was hard-coded to RM) and fully responsive desktop/mobile layout. No calculation semantics change.

**Which Android behavior is authoritative.** The Android repository contains two contradictory calculation models. The shipped one — inline `double` math in `MainActivity` using the uruf subtraction model `(weight − 85|200) × price` — is the reference for this migration and is what the web app implements (with `BigDecimal` precision). The separate `domain/` package (a nisab-threshold-on-full-weight model and its tests) is dead code: it is never invoked from the UI, contradicts the shipped behavior (e.g., 100 g kept @ 60 → 22.50 zakat due under the shipped/web model, vs. the dead tests' assertion of 6000.00 payable and 150.00 zakat due), and must not be treated as a spec or "restored" in future work.

### 1.3 Target Audience

- Practicing Muslims (Sunni and Shia) seeking a quick, trustworthy Zakat estimate on gold holdings.
- Adults (18+) of any gender who hold gold savings or jewelry, primarily in Malaysia and other Muslim-majority markets.
- Users with low-to-moderate technical literacy who value simplicity over depth.
- Users on low-bandwidth or intermittent connections; the calculator must work with no network dependency at calculation time.

### 1.4 Key User Personas

| Persona | Description | Primary Need |
|---|---|---|
| **Aisha, Working Professional** | Holds gold jewelry and savings. Revaluates yearly. | Quick calculation at revaluation time each Zakat year, on any device. |
| **Yusuf, Small Trader** | Buys and sells gold occasionally. Needs repeat calculations with different prices. | Repeat calculations with different gold values and currencies. |
| **Hassan, Learner** | Wants to understand what Zakat is and why amounts change. | Transparent math, a clear methodology page, and an honest disclaimer. |
| **Khadija, Elderly User** | Larger fonts, clear labels, minimal jargon. | Accessibility-friendly, low-clutter UI on phone and desktop. |

---

## 2. Functional Requirements

### 2.1 Core Features (MVP, v1.0)

| ID | Feature | Description |
|---|---|---|
| F1 | **Gold Weight Input** | Numeric input in grams; supports decimals. |
| F2 | **Gold Category Selector** | Binary choice: "Kept (savings)" vs. "Worn (jewelry)". Drives the uruf exemption (85g vs 200g). |
| F3 | **Gold Value Input** | Current market price per gram. |
| F4 | **Currency Selector** | ISO-4217 currency for price and results (default MYR, the Malaysian ringgit). v1.0 supported set: MYR, USD, EUR, GBP, SGD, AED, INR, CAD, AUD. |
| F5 | **Calculation** | Compute weight minus uruf, gold value subject to Zakat, and Zakat due at 2.5%. |
| F6 | **Uruf Indicator** | Show the applied uruf exemption in grams and whether holdings fall below it (Zakat not due). |
| F7 | **Result Display** | Show weight minus uruf, Zakat-payable value, and total Zakat with currency formatting. |
| F8 | **Reset** | Clear all inputs and results in a single action. |
| F9 | **Light/Dark Theme** | Follows the operating system theme with an optional manual toggle. |
| F10 | **About/Methodology Page** | App info, disclaimer, calculation methodology, and source attribution. |

### 2.2 Out of Scope (v1.0)

- Cash, silver, business assets, debts, livestock, and agricultural produce.
- User accounts, cloud sync, multi-device history, persistence of past calculations.
- Real-time or automated gold price feeds (manual entry only), including web scraping or third-party price APIs.
- Multi-language localization (English only in v1.0; architecture must keep strings centralized).
- Push or email notifications for Zakat due dates.
- School-of-thought presets beyond the standard uruf model (Hanafi/Shafi'i variants are future work).
- The Android "Share App" system share sheet. Sharing is an OS-level affordance with no web equivalent in a stateless calculator; it is explicitly out of scope for this web iteration (no Web Share API button in v1.0).

### 2.3 Future Considerations (post-v1.0)

- Additional asset categories (silver, cash, stocks, crypto).
- Locale-aware Nisab presets for different schools of thought.
- Export results as PDF or printable summary.
- Calculation history saved locally in the browser (never on a server).

---

## 3. Non-Functional Requirements

| Category | Requirement |
|---|---|
| **Usability** | A first-time user must complete a calculation in under 30 seconds. |
| **Accessibility** | All interactive elements meet WCAG AA contrast; full keyboard operability; minimum 44px tap targets on touch devices; visible focus states. |
| **Performance** | Result rendered within 200ms of form submission on a mid-range device and connection. |
| **Privacy** | Inputs are sent to the app's own server only to perform the calculation; nothing is stored, logged as analytics, or shared. No analytics, no telemetry, no third-party trackers. |
| **Reliability** | No errors on empty, negative, or overflow inputs. Graceful, inline validation messaging. |
| **Offline** | No dependency on third-party services or live price feeds: the user supplies the price, and the calculation runs entirely on the app's own server. |
| **Compatibility** | Modern evergreen browsers (last 2 versions of Chrome, Edge, Firefox, Safari) and current mobile browsers. |
| **Localization** | English only at launch; all user-facing strings must be centralized for future externalization. |
| **Currency** | Monetary results formatted with the selected ISO-4217 currency, correct minor-unit scale (2 decimals for the supported set). |

---

## 4. Key User Flows

### 4.1 Primary Flow: Calculate Zakat

1. User opens the site and lands on the **Calculator** screen.
2. User enters **gold weight in grams** (e.g., 120).
3. User selects **gold category** (Kept / Worn).
4. User enters **current gold value per gram** (e.g., 350.00) and optionally changes the currency.
5. User submits the form (**Calculate**).
6. The app displays:
   - Applied uruf exemption (e.g., 85g for kept gold)
   - Weight minus uruf
   - Gold value subject to Zakat
   - Total Zakat due (2.5% of the payable value)
   - A clear "Below uruf: Zakat not due" state when weight minus uruf is zero or negative
7. User may tap **Reset** to start over, or adjust inputs and recalculate.

### 4.2 Secondary Flow: Toggle Theme

1. User taps the **theme toggle** in the header.
2. The UI re-renders in light or dark mode instantly.
3. The choice persists across visits (stored locally in the browser only).

### 4.3 Tertiary Flow: View Methodology

1. User taps **About / Methodology** in the header.
2. The page explains how the calculation works (uruf exemption, 2.5% rate), shows the formula, links to the source project, and repeats the religious disclaimer.

---

## 5. Edge-Case Behaviors

| Scenario | Expected Behavior |
|---|---|
| Empty weight field | Inline validation error; block submission. |
| Negative or zero weight | Block submission; show "Enter a positive weight". |
| Negative or zero value | Block submission; show "Enter a positive value". |
| Weight below uruf (e.g., 50g kept) | Show the breakdown with weight minus uruf at 0 or negative, payable value 0, and "Below uruf, Zakat not due". |
| Weight exactly at uruf | Weight minus uruf is 0; Zakat not due. |
| Decimal weights/values | Accepted with full precision; monetary values rounded to 2 decimal places HALF_UP at display. |
| Extremely large values | Computation stays exact internally; display capped at a sane bound (1,000,000 g weight, validated). |
| Locale with comma decimal separator | Parsed correctly; the input always accepts dot or comma separators. |
| Unknown currency code | Rejected at input validation with a clear message. |
| Form resubmission / browser refresh | Stateless: the page recomputes from submitted inputs only; no partial-state corruption. |
| Intermittent connection | Calculator, results, and methodology all function without connectivity after assets are loaded. |

---

## 6. Success Metrics

| Metric | Target |
|---|---|
| **Time-to-first-calculation** | <= 30 seconds for a first-time user |
| **Calculation accuracy** | 100% match with the reference Android implementation on a shared golden dataset |
| **Input validation coverage** | 100% of malformed inputs handled without a server error |
| **Crash/failure-free sessions** | >= 99.5% |
| **Accessibility** | Passes automated WCAG AA checks on the calculator screen |
| **Bundle size** | Page load <= 1.5s on a mid-range connection, no third-party scripts |

---

## 7. Disclaimers

> This tool provides an **estimate only**. It is not a substitute for qualified religious counsel. Users are responsible for confirming calculations with a trusted scholar and using accurate, up-to-date gold prices and uruf thresholds applicable to their jurisdiction and school of thought. The uruf exemptions (85g kept, 200g worn) reflect the common Malaysian practice; other jurisdictions may differ.