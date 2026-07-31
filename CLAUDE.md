# CLAUDE.md — StockFlow (merged)

> **This file is authoritative.** Claude Code loads it every session. When a rule or decision
> changes, update it **here** first. The full design lives in `docs/architecture/` (SF-DOC merge).

---

## 1. Product summary

StockFlow is a **multi-business (multi-tenant) stock management web app**, sold as a subscription,
for small/local retailers (initial market: Rwanda). Goods flow **supplier → stock room(s) →
shop(s)**. Each business signs in with its **TIN + email + password** and gets a completely
isolated workspace. Mobile-first, dark-mode first-class, bilingual **EN + RW**. It is **stock
only** — not a POS, warehouse/logistics, or accounting system.

**Four roles** (a user has exactly one): `owner` (read-only, gets the daily report) · `admin`
(sets up the workspace) · `stock_manager` (runs stock rooms, fulfils requests) · `seller` (shop
floor, **assigned to one shop, sees only it**).

The flagship flow is **seller → stock manager**: a seller sees availability by location, raises a
request, the stock manager fulfils it as a recorded transfer, and the goods then show in the shop
(`request → accept → fulfil`).

---

## 2. Tech stack (decided — do NOT change without an ADR)

- **One Next.js codebase** (App Router, **TypeScript**). Frontend + API in one repo.
- **API** = Next.js **Route Handlers** under `src/app/api/**`.
- **DB:** PostgreSQL 16 (Docker). **Drizzle ORM** + drizzle-kit; `postgres.js` driver.
- **Validation:** **Zod**, schemas **shared** between client forms and server handlers.
- **Auth:** **session cookies** (httpOnly, Secure, SameSite=Strict) + server-side `sessions` table;
  argon2id hashing.
- **i18n:** **next-intl**, locales `en` + `rw` (ported from the NT foundation).
- **Client state:** TanStack Query (incl. the 30 s request-queue poll).
- **PKs:** UUID v7 (app-generated). **Money:** `bigint` minor units. **Quantities:**
  `numeric(14,3)`.
- **Testing:** Vitest (unit + integration), Playwright (e2e). **CI:** GitHub Actions.

The prior NT Laravel + separate-frontend monorepo is preserved under `legacy/` for reference only.

---

## 3. The rules that matter (enforce from commit #1)

1. **Tenant isolation is a database guarantee, not just code.** Every tenant table carries
   `business_id`, has **RLS enabled + forced + a policy**, and the app connects as `sf_app`
   **without `BYPASSRLS`**. Every request sets `SET LOCAL app.business_id` inside a transaction
   (`withBusinessContext`). No context ⇒ **zero rows**. Any cross-tenant leak is a **critical bug**.
2. **Shop-level privacy** composes on top: a `seller` sees only their assigned shop; company-wide
   roles see all. Another shop's resource returns **404** to a seller.
3. **Quantity is derived** from immutable movements, **per location**. There is **no quantity
   column on products**. Corrections are **reversals**, never edits.
4. **Server decides, client displays.** Every authorization + validation decision is server-side;
   the permission matrix is enforced on every request (`docs/architecture/01-requirements.md §3`).
5. **No hard-coded user-facing text.** Every string is a key present in **both** `en` and `rw`;
   the key sets must match (CI-enforced). Business data (product/unit/location names) is never
   translated.
6. **Layering:** route handler (parse → guard → call service → map envelope) → service (all rules,
   owns transactions, throws typed domain errors) → repository (Drizzle SQL only). A rule in a
   handler or an auth check in a repository is rejected in review.
7. **Money = minor units; quantities = exact decimals.** Never float. Parameterised queries only
   (a lint rule forbids template-literal SQL). No `dangerouslySetInnerHTML`. No direct
   `process.env` outside `src/lib/env.ts`.
8. Deactivate, never delete, anything referenced by history. Every important action writes an
   append-only audit entry.

---

## 4. API envelope (kept from NT)

Success: `{ "success": true, "data": …, "message"?: "localized", "meta"? }`.
Error: `{ "success": false, "message": "localized", "code"?, "errors"?: { field: [...] },
"details"?, "requestId" }`. Correct status codes (200/201/204, 400/401/402/403/404/409/422/429/500).
Auth failures reveal nothing about which field was wrong. See `docs/architecture/04-api-specification.md`.

---

## 5. Folder structure (target)

```
stockflow/
├── src/
│   ├── app/                         App Router: (auth) (app) api/**
│   ├── components/{ui,layout}/       shared presentational UI + shells
│   ├── features/                     client hooks + views (mirror api modules)
│   ├── hooks/                        useAuth, useBusiness, usePolling, useI18n
│   └── lib/
│       ├── db/{schema,migrations}, tenant.ts (withBusinessContext), index.ts
│       ├── server/<module>/          service layer (business rules)
│       ├── api/                      handler helpers (session, requireRole, validate, envelope)
│       ├── schemas/                  Zod (SHARED)
│       ├── i18n/                     next-intl config + messages
│       ├── permissions.ts, units.ts, money.ts, quantity.ts, dates.ts, env.ts
├── worker/                           dailyReport, cleanup, subscriptions [P2]
├── drizzle/                          generated SQL migrations
├── scripts/                          seed, seed-perf, import-opening-stock
├── legacy/                           NT Laravel + Next reference (do not build here)
└── docker-compose.yml                Postgres 16
```

## 6. Git & commits
Branches `main` (stable) / `develop`. Conventional Commits (`feat:`, `fix:`, `chore:`, `docs:`,
`test:`, `build:`, `refactor:`). Small logical commits; write the test with each feature. Review
every change for the two easiest ways to erode the foundation: **hard-coded strings** and
**cross-tenant/cross-shop access**.

## 7. What is NOT built yet
Follow `docs/architecture/05-implementation-plan.md` ticket order. Phase 2 (billing) only after
Phase 1 is signed off. No supplier records (receipts carry an optional free-text `supplier_ref`),
no POS, no accounting, no barcode, no batch/expiry, no multi-currency within a business.
