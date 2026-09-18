---
name: skill-registry
description: "Complete registry of all available skills — global installs, local project skills, and skills available in both scopes. Use when looking up skill names, descriptions, locations, or scope."
allowed-tools:
  - Read
  - Grep
  - Glob
---

# Skill Registry

This file documents all available skills installed globally and locally for this project.

## Summary

| Scope | Count | Path |
|-------|-------|------|
| Global | 115 | `C:\Users\exatf\.claude\skills\` |
| Local (project) | 39 | `D:\Projects\Zakat-Calculator-Laravel\.agents\skills\` |
| Both | 32 | — |

---

## Global Skills (all)

Located at `C:\Users\exatf\.claude\skills\`. These are available to all sessions.

| Skill | Description |
|-------|-------------|
| `_gstack-command` | Router for the gstack skill suite. (gstack) |
| `animate` | Build an animation from scratch — should it animate, what purpose, which tool, which properties, which curve and duration, how it interrupts, how it exits. |
| `animate-expo` | Build animations in React Native and Expo — Reanimated, Gesture Handler, Expo Router, expo-haptics. |
| `animation-vocabulary` | Reverse-lookup glossary turning vague animation descriptions into exact terms. |
| `antislop` | Anti Slop: Rules for AI Coding Agents. The core filter. Load always to stop generic AI slop. |
| `antislop-code` | Code comment hygiene for AI coding agents: remove generic AI-slop comments, keep the valuable ones. |
| `antislop-copywriting` | Copy and text skill for antislop — headlines, tone, CTAs, anti-AI-writing patterns. |
| `antislop-human` | Human and accessibility skill for antislop — contrast, keyboard, focus, states. |
| `antislop-layoutmobile` | Mobile layout skill for antislop — grids, overflow, tap targets. |
| `antislop-ui` | UI and visual skill for antislop — color, layout, components, motion. |
| `api-and-interface-design` | Guides stable API and interface design — REST/GraphQL endpoints, type contracts, module boundaries. |
| `apple-design` | Apple's approach to UI design and fluid, physical motion — spring animations, gesture-driven UI, materials, typography. |
| `ask-sonner` | Guide to Sonner — toast library install, wire up, toast calls, promise/loading toasts, styling, theming. |
| `autoplan` | Auto-review pipeline — reads CEO, design, eng, DX review skills and runs them sequentially with auto-decisions. |
| `benchmark` | Performance regression detection — page load times, Core Web Vitals, resource sizes. |
| `benchmark-models` | Cross-model benchmark for gstack skills — Claude, GPT, Gemini side-by-side comparison. |
| `brandkit` | Premium brand-kit image generation — brand-guidelines boards, logo systems, identity decks. |
| `browse` | Drive a real browser — open a page, read it, click through a flow, take screenshots, check console errors. |
| `browser-testing-with-devtools` | Tests in real browsers via Chrome DevTools MCP — DOM inspection, console errors, network, profiling. |
| `canary` | Post-deploy canary monitoring — console errors, performance regressions, page failures, screenshots. |
| `careful` | Safety guardrails for destructive commands — warns before rm -rf, DROP TABLE, force-push, etc. |
| `ci-cd-and-automation` | Automates CI/CD pipeline setup — quality gates, test runners, deployment strategies. |
| `code-review-and-quality` | Conducts multi-axis code review before merging any change. |
| `code-simplification` | Simplifies code for clarity — refactoring without changing behavior, reducing complexity. |
| `codex` | OpenAI Codex CLI wrapper — code review, challenge (adversarial), consult with session continuity. |
| `connect-chrome` | Launch GStack Browser — AI-controlled Chromium with the sidebar extension baked in. |
| `constraint-driven-development` | Establishes a project's quality bar as a written contract — thresholds, CONSTRAINTS.md, monitoring. |
| `context-engineering` | Optimizes agent context setup — rules files, configuration for projects. |
| `context-restore` | Restore working context saved earlier by /context-save — git state, decisions, remaining work. |
| `context-save` | Save working context — git state, decisions, remaining work for future sessions. |
| `cso` | Security audit — supported static findings; qualified profiles add reproduction and repair candidates. |
| `debugging-and-error-recovery` | Guides systematic root-cause debugging — investigate, analyze, hypothesize, implement. |
| `deprecation-and-migration` | Manages deprecation and migration — removing old systems, expanding/contracting schema changes. |
| `design-consultation` | Design consultation — researches the landscape, proposes a complete design system, generates DESIGN.md. |
| `design-html` | Design finalization — production-quality Pretext-native HTML/CSS with real text reflow. |
| `design-review` | Designer's eye QA — visual inconsistency, spacing, hierarchy, AI slop patterns, slow interactions. |
| `design-shotgun` | Design shotgun — generate multiple AI design variants, open a comparison board, collect feedback. |
| `design-taste-frontend` | Anti-slop frontend skill for landing pages and portfolios — reads the brief, infers direction. |
| `design-taste-frontend-v1` | Original v1 taste-skill, preserved for projects depending on its exact behavior. |
| `devex-review` | Live developer experience audit — tests DX in browser, navigates docs, times TTHW, screenshots error messages. |
| `diagram` | Turn English descriptions into diagram triplets — source, editable Excalidraw file, rendered SVG/PNG. |
| `document-generate` | Generate missing documentation from scratch — Diataxis framework (tutorial/how-to/reference/explanation). |
| `document-release` | Post-ship documentation update — cross-references diff, updates README/ARCHITECTURE/CONTRIBUTING/CLAUDE.md. |
| `documentation-and-adrs` | Records decisions and documentation — architecture decisions (ADR), reasoning, API changes. |
| `doubt-driven-development` | Subjects every non-trivial decision to fresh-context adversarial review before it stands. |
| `emil-design-eng` | Emil Kowalski's philosophy on UI polish, component design, animation decisions, invisible details. |
| `find-animation-opportunities` | Search a codebase/UI for places that should animate — proposes motion with exact values, read-only. |
| `find-skills` | Helps users discover and install agent skills — keyword search for functionality. |
| `freeze` | Restrict file edits to a specific directory for the session — blocks Edit/Write outside allowed path. |
| `frontend-ui-engineering` | Builds production-quality, accessible, responsive user-facing UIs — components, layouts, WCAG, state. |
| `full-output-enforcement` | Enforces complete code generation, bans placeholder patterns, handles token-limit splits cleanly. |
| `git-workflow-and-versioning` | Structures git workflow practices — commits, branches, PRs, release, changelog. |
| `gpt-taste` | Elite UX/UI & Advanced GSAP Motion Engineer — Python-driven randomization, AIDA structure, GSAP ScrollTriggers. |
| `gstack` | Router for the gstack skill suite — planning, review, QA, shipping, debugging, docs, security, design. |
| `gstack-upgrade` | Upgrade gstack to the latest version — detects global vs vendored install, runs upgrade. |
| `guard` | Full safety mode — destructive command warnings + directory-scoped edits (combines /careful + /freeze). |
| `health` | Code quality dashboard — wraps type checker, linter, test runner, computes weighted composite score. |
| `high-end-visual-design` | Teaches high-end agency design — fonts, spacing, shadows, card structures, animations. |
| `humanizer` | Rewrite AI-sounding text so it reads like the writer — not-X-but-Y, one-line closers, staged openers. |
| `idea-refine` | Refines raw ideas into sharp, actionable concepts — structured divergent and convergent thinking. |
| `image-to-code` | Elite website image-to-code skill — generates design images, deep analysis, implements matching website. |
| `imagegen-frontend-mobile` | Premium mobile app image-generation — app-native screen concepts and flows. |
| `imagegen-frontend-web` | Elite frontend image-direction — conversion-aware website design references, section-by-section. |
| `impeccable` | Design, redesign, shape, critique, audit, polish, clarify, distill, harden, optimize any frontend interface. |
| `improve-animations` | Survey animation/motion code, produce prioritized audit and implementation plans — read-only. |
| `incremental-implementation` | Delivers changes incrementally in thin, verifiable slices — good for multi-file features. |
| `industrial-brutalist-ui` | Raw mechanical interfaces — Swiss typographic print + military terminal aesthetics. |
| `interview-me` | Extracts what the user actually wants — one-question-at-a-time interview until ~95% confidence. |
| `investigate` | Systematic debugging with root cause investigation — four phases: investigate, analyze, hypothesize, implement. |
| `ios-clean` | Remove DebugBridge SPM package and #if DEBUG wiring from iOS app. |
| `ios-design-review` | Visual design audit for iOS apps on real hardware — screenshots, HIG evaluation, scoring. |
| `ios-fix` | Autonomously fix iOS bugs — reads source, writes fix, rebuilds, redeploys, verifies on device. |
| `ios-qa` | Live-device iOS QA for SwiftUI apps — vision-driven agent loop via USB/wifi. |
| `ios-sync` | Regenerate iOS debug bridge against latest upstream gstack templates. |
| `learn` | Manage project learnings — review, search, prune, export what gstack has learned. |
| `land-and-deploy` | Land and deploy workflow — merges PR, runs tests, reviews diff, bumps VERSION, creates PR. |
| `landing-report` | Read-only queue dashboard for workspace-aware ship — VERSION slots, open PRs, next slot. |
| `make-pdf` | Turn markdown into publication-quality PDF — margins, page breaks, TOC, watermark. |
| `minimalist-ui` | Clean editorial-style interfaces — warm monochrome palette, typographic contrast, flat bento grids. |
| `open-gstack-browser` | Launch GStack Browser — AI-controlled Chromium with sidebar extension. |
| `pair-agent` | Pair a remote AI agent with your browser — setup key, interactive picker, trust boundary. |
| `performance-optimization` | Optimizes application performance — frontend, backend, queries, databases, N+1, caching. |
| `pick-ui-library` | Pick the right library for a frontend task from a curated, opinionated list. |
| `plan-ceo-review` | CEO/founder-mode plan review — rethink the problem, find the 10-star product, challenge premises. |
| `plan-design-review` | Designer's eye plan review — rates design dimensions 0-10, explains what would make it a 10. |
| `plan-devex-review` | Interactive developer experience plan review — developer personas, benchmarks, magical moments. |
| `plan-eng-review` | Eng manager-mode plan review — architecture, data flow, diagrams, edge cases, test coverage. |
| `plan-tune` | Self-tuning question sensitivity + developer psychographic for gstack. |
| `planning-and-task-breakdown` | Breaks work into ordered tasks — scope estimation, parallel work identification. |
| `prototype` | Build multiple genuinely different UI versions behind a visual picker — promote the one that feels right. |
| `qa` | Systematically QA test a web application and fix bugs — iterative fix-verify loop with before/after health. |
| `qa-only` | Report-only QA testing — structured report with health score, screenshots, repro steps (no fixes). |
| `redesign-existing-projects` | Upgrades existing websites/apps to premium quality — audits design, identifies AI patterns. |
| `retro` | Weekly engineering retrospective — commit history, work patterns, code quality, team-aware. |
| `review` | Pre-landing PR review — SQL safety, LLM trust boundaries, conditional side effects. |
| `review-animations` | Reviews animation/motion code against high craft bar derived from Emil Kowalski's design engineering. |
| `scrape` | Pull data from a web page through Aside browser — real, signed-in sessions (read-only). |
| `security-and-hardening` | Hardens code against vulnerabilities — input handlers, auth, data storage, OWASP Top Ten. |
| `setup-browser-cookies` | Import cookies from real Chromium browser into headless browse session. |
| `setup-deploy` | Configure deployment settings for /land-and-deploy — platform, URL, health checks. |
| `setup-gbrain` | Set up gbrain — install CLI, initialize brain, register MCP, capture trust policy. |
| `ship` | Ship workflow — detect/merge base branch, run tests, review diff, bump VERSION, update CHANGELOG, create PR. |
| `shipping-and-launch` | Prepares production launches — pre-launch checklist, monitoring, staged rollout, rollback strategy. |
| `skillify` | Codify successful /scrape flow into permanent browser-skill on disk. |
| `source-driven-development` | Grounds every implementation decision in official documentation — source-cited code. |
| `spec` | Turn vague intent into a precise, executable spec in five phases — files issues, spawns agents. |
| `spec-driven-development` | Creates specs before coding — capability maps, structured requirements. |
| `stitch-design-taste` | Semantic Design System Skill for Google Stitch — premium anti-generic UI standards. |
| `sync-gbrain` | Keep gbrain current with repo code and refresh agent search guidance in CLAUDE.md. |
| `test-driven-development` | Drives development with red-green-refactor loop — tests for logic, bug fixes, behavior changes. |
| `unfreeze` | Clear the freeze boundary set by /freeze — allows edits to all directories again. |
| `using-agent-skills` | Discovers and invokes agent skills — meta-skill governing how other skills are found and used. |
| `write-swift` | Modern Swift well — value types, concurrency, protocols, generics, testing, macros, language features. |

---

## Local (Project) Skills

Located at `D:\Projects\Zakat-Calculator-Laravel\.agents\skills\`. These are project-specific.

| Skill | Description |
|-------|-------------|
| `antislop` | Anti Slop: Rules for AI Coding Agents. The core filter. |
| `antislop-code` | Code comment hygiene for AI coding agents. |
| `antislop-copywriting` | Copy and text skill for antislop — headlines, tone, CTAs. |
| `antislop-human` | Human and accessibility skill for antislop — contrast, keyboard, focus, states. |
| `antislop-layoutmobile` | Mobile layout skill for antislop — grids, overflow, tap targets. |
| `antislop-ui` | UI and visual skill for antislop — color, layout, components, motion. |
| `api-and-interface-design` | Stable API and interface design — REST/GraphQL endpoints, type contracts. |
| `browser-testing-with-devtools` | Tests in real browsers via Chrome DevTools MCP. |
| `ci-cd-and-automation` | Automates CI/CD pipeline setup. |
| `code-review-and-quality` | Multi-axis code review before merging any change. |
| `code-simplification` | Simplifies code for clarity — refactoring without changing behavior. |
| `constraint-driven-development` | Project quality bar as written contract — thresholds, CONSTRAINTS.md. |
| `context-engineering` | Optimizes agent context setup — rules files, project configuration. |
| `debugging-and-error-recovery` | Systematic root-cause debugging — investigate, analyze, hypothesize, implement. |
| `deploying-to-cloud` | Deploys Laravel apps on Laravel Cloud — apps, environments, databases, compute. |
| `deprecation-and-migration` | Deprecation and migration — removing old systems, expanding/contracting schema. |
| `documentation-and-adrs` | Records decisions and documentation — ADRs, reasoning, public API changes. |
| `doubt-driven-development` | Adversarial review of every non-trivial decision before it stands. |
| `frontend-ui-engineering` | Production-quality accessible responsive UIs — components, layouts, WCAG, state. |
| `git-workflow-and-versioning` | Git workflow practices — commits, branches, PRs, releases, changelog. |
| `idea-refine` | Refines raw ideas into actionable concepts — divergent/convergent thinking. |
| `impeccable` | Design/review/polish any frontend interface — visual, a11y, responsive, theming. |
| `incremental-implementation` | Delivers changes incrementally in thin, verifiable slices. |
| `infer-conventions` | Analyzes how a Laravel app is written and records its conventions as shared rules. |
| `interview-me` | Extracts what the user actually wants via one-question-at-a-time interview. |
| `jev-eval` | TypeSafe AI Jev — instant typed evaluations (vulnerability, severity, context) on code snippets. |
| `laravel-best-practices` | Laravel best practices — controllers, models, migrations, test requests, query performance. |
| `observability-and-instrumentation` | Instruments code for production visibility — logging, metrics, tracing, alerting. |
| `performance-optimization` | Performance optimization — frontend, backend, queries, databases, caching. |
| `planning-and-task-breakdown` | Breaks work into ordered tasks — scope estimation, parallel work. |
| `security-and-hardening` | Hardens code against vulnerabilities — auth, input, data storage, OWASP. |
| `shipping-and-launch` | Production launch preparation — checklist, monitoring, rollout, rollback. |
| `source-driven-development` | Grounds decisions in official documentation — source-cited code. |
| `spec-driven-development` | Creates specs before coding — capability maps, structured requirements. |
| `tailwindcss-development` | Tailwind CSS utility classes — responsive grids, components, dark mode, styling. |
| `test-driven-development` | Red-green-refactor loop — tests for logic, bugs, behavior changes. |
| `testing-best-practices` | Laravel test design — coverage, naming, structure, isolation, value. |
| `typesafe-ai` | Build AI-powered software with TypeSafe — typed judgments, common sense, structured decisions. |
| `using-agent-skills` | Discovers and invokes agent skills — meta-skill for finding and using skills. |

---

## Skills in Both Global and Local

The following 32 skills are installed both globally and locally, meaning the local copy takes precedence when working in this project:

| Skill | Scope |
|-------|-------|
| `antislop` | Both |
| `antislop-code` | Both |
| `antislop-copywriting` | Both |
| `antislop-human` | Both |
| `antislop-layoutmobile` | Both |
| `antislop-ui` | Both |
| `api-and-interface-design` | Both |
| `browser-testing-with-devtools` | Both |
| `ci-cd-and-automation` | Both |
| `code-review-and-quality` | Both |
| `code-simplification` | Both |
| `constraint-driven-development` | Both |
| `context-engineering` | Both |
| `debugging-and-error-recovery` | Both |
| `deprecation-and-migration` | Both |
| `documentation-and-adrs` | Both |
| `doubt-driven-development` | Both |
| `frontend-ui-engineering` | Both |
| `git-workflow-and-versioning` | Both |
| `idea-refine` | Both |
| `impeccable` | Both |
| `incremental-implementation` | Both |
| `interview-me` | Both |
| `security-and-hardening` | Both |
| `shipping-and-launch` | Both |
| `source-driven-development` | Both |
| `spec-driven-development` | Both |
| `observability-and-instrumentation` | Both |
| `performance-optimization` | Both |
| `planning-and-task-breakdown` | Both |
| `test-driven-development` | Both |
| `using-agent-skills` | Both |
