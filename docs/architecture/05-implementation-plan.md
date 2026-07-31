# 05 — Implementation Plan

Ordered, ticketed roadmap for the merged system. Adapts the STOCK pack's bootstrap to the
**Next.js full-stack + Drizzle + RLS** stack, reusing what the NT foundation already proved.

---

## 0. Migration strategy from the NT foundation

The NT code is Laravel; we are moving to Next.js. We **keep the ideas and the frontend, port the
backend**:

- **Reuse directly:** the Next.js frontend (`app/`, components, `next-intl` `en`/`rw`, the
  `ApiResponse` envelope shape, `LoginForm`, `AuthContext`, `apiClient`), the docker-compose
  Postgres service, the CI workflow skeleton, the TIN rule idea, the shop-privacy concept.
- **Port, don't copy:** Laravel models/migrations/services → Drizzle schema + `lib/server`
  services. `companies`→`businesses`, `shops`→`locations(kind='shop')`, `TenantScope`→RLS + scope
  helper, Sanctum → session cookies, bigint → UUID v7, JS → TS.
- **Fresh:** the entire stock engine (products/movements/requests) — NT never built it, so there is
  nothing to port; build it to [03-database-design](./03-database-design.md) and [02 §7–8](./02-system-architecture.md).

---

## 1. Ticketed roadmap

Each ticket names the requirement IDs it satisfies and the docs to follow. **Bound the blast
radius**: a ticket touches only its module + its tests.

### Milestone A — Foundation & tenancy (the part that matters)
| Ticket | Title | Requirements | Notes |
|---|---|---|---|
| SF-001 | Scaffold Next.js monorepo + tooling | — | TS strict, Tailwind, ESLint/Prettier (rules: no template-literal SQL, no `dangerouslySetInnerHTML`), Vitest, Playwright, the folder tree in [02 §4]. |
| SF-002 | Local env + config | NFR-MNT-03 | docker-compose Postgres 16 (from NT), `.env.example`, Zod-validated env that exits on invalid, drizzle-kit wired. |
| SF-003 | **Migration 001 + tenancy + RLS** ⚑ | FR-TEN-01/02, BR-14, NFR-SEC-04 | roles (`sf_migrate/sf_app/sf_reversal/sf_readonly`), `businesses`+TIN trigger, `users`, `sessions`, `audit_log`, `business_settings`. RLS enabled+forced+policy on every tenant table. `withBusinessContext`. **CI guard** enumerating tenant tables. *Author by hand, review the design before the code.* |
| SF-004 | Tenant-isolation + RLS-guard test suites | TC-TEN-* | Generated from the catalogue: for every table with `business_id`, business A reads zero of B; insert carrying B's id refused by WITH CHECK; no-context read = zero rows; pooled connection reused across two requests shows only its own data. `rls-guard` asserts relrowsecurity+relforcerowsecurity+policy on each. Add both to required CI. |
| SF-005 | Auth (session cookies) | FR-AUTH-01…10 | Login by TIN+email+password, argon2id, lockout, reset, change-password, first-login password set, deactivation kills sessions. Port NT's `AuthService`/`LoginForm`. |
| SF-006 | i18n plumbing | FR-UIX-07 | Port NT `next-intl` `en`/`rw`; ICU messages; **CI key-parity check** (en/rw key sets identical); server error localization from `Accept-Language`→locale→`en`. |
| SF-007 | CI, branch protection, first push | NFR-MNT-02, NFR-SEC-11/12 | GitHub Actions: install→lint→typecheck→migrate-from-empty→unit→integration→rls-guard→tenant-isolation→i18n-parity→secret-scan (gitleaks). |

### Milestone B — Catalogue (many stock rooms + many shops)
| Ticket | Title | Requirements |
|---|---|---|
| SF-010 | Migration 002 + locations | FR-LOC-01…10 (many of each) |
| SF-011 | Units (dynamic, seeded 22) | FR-UOM-01…07 |
| SF-012 | Categories | FR-CAT-01…06 |
| SF-013 | Products (+ trigram search, per-location breakdown) | FR-PRD-01…17 |
| SF-014 | User management + shop assignment + shop-privacy scope | FR-USR-01…06, FR-SEC-02b |

### Milestone C — Stock engine & the workflow (the heart)
| Ticket | Title | Requirements | Notes |
|---|---|---|---|
| SF-020 | Migration 003 + stock_levels + movement_reasons | FR-MOV-08 | Seed reasons per business. |
| SF-021 | **The stock transaction** ⚑ | FR-MOV-01…20, BR-01…07 | Deterministic lock order; four types; reversal; idempotency; concurrency + reconciliation tests. *Author by hand; never let the agent write the paired tests unreviewed.* |
| SF-022 | **The request workflow** ⚑ | FR-REQ-01…16, PS-8 | request→accept→fulfil (one tx transfer to the seller's shop); partial; reject; cancel; 30 s poll; in-app notify. |
| SF-023 | Movement/request UI (mobile-first, ≤4 taps) | FR-MOV-17, FR-DSH-07 | MobileShell sheets. |

### Milestone D — Visibility
| Ticket | Title | Requirements |
|---|---|---|
| SF-030 | Dashboards (4 roles, one call, no money for stock_mgr/seller) | FR-DSH-01…09 |
| SF-031 | Reports (8 + CSV) | FR-REP-01…12 |
| SF-032 | Daily report + worker | FR-DAY-01…09 |
| SF-033 | Audit log UI | FR-SYS-01…03 |

### Milestone E — Hardening → Phase 1 go-live
| Ticket | Title | Requirements |
|---|---|---|
| SF-040 | Performance pass (seed-perf, budgets) | NFR-PERF-01…08 |
| SF-041 | Security pass (CSP, rate limits, headers, secret scan) | NFR-SEC-* |
| SF-042 | a11y + WCAG 2.1 AA | FR-UIX-06 |
| SF-043 | Opening-stock import + UAT | §6.6 |

### Milestone F — Phase 2 (SaaS)
| Ticket | Title | Requirements |
|---|---|---|
| SF-050 | Migration 004 + plans/subscriptions/payments | FR-TEN-07…11 |
| SF-051 | Registration + TIN lookup + onboarding wizard | FR-TEN-04…06 |
| SF-052 | Payment adapter + webhooks (idempotent) | FR-TEN-09/10, NFR-MNT-07 |
| SF-053 | Subscription lifecycle + guard + banners | FR-TEN-11…14, UC-07 |
| SF-054 | Invoices, self-service, workspace export | FR-TEN-15…18 |

⚑ = a "paired" ticket (tenancy, the stock transaction, the request workflow, reversal, payment
webhooks). Design-review before code; author the tests by hand; run the **deletion test** (delete
the production line a test protects — if the test still passes, it is not a test).

---

## 2. Day-1 bootstrap sequence

Run these in order; each ends green before the next. Prompts should **name the ticket + requirement
IDs + the doc section**, and **bound the blast radius**.

1. **SF-001** — scaffold. Verify: `npm install && npm run verify` green; `npm run dev` serves the
   app; login page renders.
2. **SF-002** — env + Postgres. Verify: `npm run setup` (compose up → wait healthy → migrate →
   seed) works from a clean clone.
3. **SF-003 + SF-004** — *the one that matters.* Verify by hand, not only by reading the diff:
   `npm run db:reset && npm run test -- tenant-isolation rls-guard` green. Then **break it on
   purpose** — drop one policy, re-run → must go red — and put it back.
4. **SF-006** — i18n. Verify: en/rw key-parity test green.
5. **SF-005** — auth. Verify: sign in as the seeded admin; wrong credentials give the generic
   message; deactivation kills the session.
6. **SF-007** — CI + branch protection (include administrators). First push.
7. **Audit gate** — before any feature: confirm the folder tree matches [02 §4]; every tenant table
   has `business_id` + RLS enabled + forced + policy; `sf_app` has no BYPASSRLS and no UPDATE/DELETE
   on `movements`/`audit_log`; no direct `process.env` outside config; no template-literal SQL; no
   user-facing literal in components; en/rw key sets identical. Fix findings, then build features.

---

## 3. Root scripts (referenced throughout)
`dev` · `build` · `start` · `verify` (= lint + typecheck + test) · `lint` · `lint:fix` · `format` ·
`typecheck` · `test` · `test:e2e` · `db:generate` · `db:migrate` · `db:rollback` · `db:reset` ·
`seed` · `seed:perf` · `setup` (install → compose up → wait healthy → migrate → seed).

---

## 4. Prompting rules for the rest of the build (from SF-DOC bootstrap §8)
1. Name the ticket + requirement IDs; point at the doc section, don't paraphrase.
2. Bound the blast radius — end every prompt with the exact paths it may touch.
3. Ask for the plan first on anything non-trivial.
4. Commit before a big prompt; read the whole diff before pushing.
5. Never let the agent author the paired tickets (⚑) unreviewed.
6. Run the deletion test on any test it wrote.
7. When it repeats a mistake, fix the project's `CLAUDE.md`, not just the diff.

**Prompt template**
```
Implement SF-0NN — <title>.
Requirements: <FR-…> in docs/architecture/01-requirements.md §<n>.
Contract:     docs/architecture/04-api-specification.md §<n>.
Schema:       docs/architecture/03-database-design.md §<n> — the table exists; do not change it.
Layering:     docs/architecture/02-system-architecture.md §4 (repository→service→handler).
Write only in <exact paths> and its test file.
Remember: every query runs inside withBusinessContext; another business/shop returns 404;
quantities are exact decimals; money is minor units; no user-facing literal strings.
Do not touch files outside those paths. Do not add dependencies. Stop and ask if a requirement
is ambiguous.
```

---

## 5. What is NOT built until requested
Phase-2 billing before Phase 1 is signed off; product images; PDF report export (CSV first);
barcode scanning; supplier records / purchase orders; POS; accounting; batch/expiry tracking;
multi-currency within one business; native mobile apps; offline mode. (SF-DOC-02 §6.4 exclusions
carry over.)
