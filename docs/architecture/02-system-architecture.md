# 02 — System Architecture & Technical Design

Merges SF-DOC-04 (rigor: RLS, the stock transaction, the request workflow) with the NT reality
(Next.js, next-intl, the API envelope), on **one Next.js full-stack codebase (TypeScript)**.

---

## 1. Guiding principles (unchanged from SF-DOC-04 §1)

1. **Boring by default** — widely-used, well-documented components; novelty spent only where the
   problem is genuinely unusual (it isn't, in stock management).
2. **The database is the guardian** — non-negative stock, immutable movements, and tenant isolation
   are enforced by DB constraints/policies as well as code. Application code can be bypassed;
   constraints cannot.
3. **One source of truth per fact** — a quantity is the signed sum of movement effects at a
   location. Nothing else writes it.
4. **Server decides, client displays** — every authorization and validation decision is server-side.
5. **Tenancy is structural, not a filter** — `business_id` on every table from migration 001,
   enforced by RLS.
6. **Small & vertical** — features are thin vertical slices.
7. **No cleverness in the stock path** — the code that changes stock is the plainest, most tested
   code in the repo.

---

## 2. Architecture overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│ CLIENTS   Phone (320–767px): Seller · Stock manager                      │
│           Desktop (≥1024px): Administrator · Owner        HTTPS only     │
└───────────────────────────────────┬─────────────────────────────────────┘
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ NEXT.JS APP (App Router, TypeScript) — ONE codebase                      │
│                                                                          │
│  app/(marketing) · app/(auth) · app/(app)  ── React Server + Client      │
│    components, next-intl (en|rw), Tailwind, TanStack Query               │
│                                                                          │
│  app/api/**  ── Route Handlers = the REST API                            │
│    ┌────────┐ ┌─────────┐ ┌───────────────┐ ┌────────┐ ┌────────────┐    │
│    │ handler│→│ session │→│ BUSINESS      │→│ role   │→│ validation │    │
│    │  route │ │  load   │ │ CONTEXT ★     │ │ guard  │ │ (Zod)      │    │
│    └────────┘ └─────────┘ └──────┬────────┘ └────────┘ └─────┬──────┘    │
│      lib/server/ ── SERVICE LAYER (all business rules, owns transactions)│
│      lib/db/     ── REPOSITORY (Drizzle, parameterised SQL, transactions)│
└───────────────────────────────────┬─────────────────────────────────────┘
                    SET LOCAL app.business_id (per request tx)
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ POSTGRESQL 16 — connected as sf_app, WITHOUT bypassrls                    │
│  ★ ROW-LEVEL SECURITY: every tenant table filtered by app.business_id    │
│    + CHECKs, partial unique indexes, append-only triggers                │
└───────────────────────────────────────────────────────────────────────────┘
        │                    │                    │                 │
    ┌───────┐          ┌──────────┐        ┌────────────┐    ┌─────────────┐
    │Worker │          │ Mail     │        │ Log/error  │    │ Payment     │
    │daily  │          │ invites  │        │ collector  │    │ provider    │
    │report │          │ resets   │        │            │    │ adapter [P2]│
    │cleanup│          │ daily rpt│        └────────────┘    └─────────────┘
    └───────┘          └──────────┘
```

**Why one codebase (vs SF-DOC's separate api/web).** You chose a single Next.js app. The payoff:
Zod schemas, domain types, the role→capability map, unit rules, and the i18n message contract are
shared *directly* between server route handlers and client components — no drift, no duplicated
types, no cross-package build step. The trade-off (accepted): the API and UI deploy together.

**Why RLS with Next.js.** Route handlers run per-request in Node; the tenant-context middleware
opens a transaction, issues `SET LOCAL app.business_id`, and runs the handler's DB work inside it —
identical to SF-DOC-04 §5 step 9. A handler that reads without context reads **zero** rows.

---

## 3. Technology stack

| Layer | Choice | Why |
|---|---|---|
| Framework | Next.js 15+ App Router, TypeScript strict | One codebase; RSC + route handlers |
| UI | React 19 + Tailwind CSS | NT base; mobile-first |
| i18n | next-intl (en, rw) | NT's, already working; ICU messages |
| Client state | TanStack Query | Caching, invalidation, request-queue polling |
| Forms/validation | React Hook Form + **Zod** (shared) | Same schema both ends |
| Charts | Recharts | Light enough for the budget |
| API | Next.js Route Handlers (`app/api/**`) | The REST surface |
| DB | PostgreSQL 16 | RLS, exact `numeric`, `FOR UPDATE` |
| DB access | **Drizzle ORM** + `postgres.js`, drizzle-kit migrations | Typed SQL, explicit tx & locks |
| Auth | Session cookie + server-side `sessions` table | Instant revocation; carries `business_id` |
| Hashing | argon2 (argon2id) | NFR-SEC-02 |
| Scheduler | Node worker (`worker/`) via node-cron | Daily report, cleanup, subscription sweep |
| Payments `[P2]` | Provider adapter interface | Swappable (mobile money, card) |
| Testing | Vitest (unit+integration), Playwright (e2e) | One runner; NT already uses Vitest |
| CI | GitHub Actions | Extend NT's workflow |

**Deliberately not used:** an ORM in the stock path's lock logic (use Drizzle's raw `sql`
+ `.for('update')`), GraphQL, microservices, Redis (sessions/rate-limits/queue live in Postgres at
this scale), WebSockets (30 s polling meets FR-REQ-13). A tenant-per-DB topology (RLS instead).

---

## 4. Repository structure

```
stockflow/
├── src/
│   ├── app/
│   │   ├── (marketing)/                 public pages
│   │   ├── (auth)/login, /reset         auth screens
│   │   ├── (app)/                       authenticated shells
│   │   │   ├── dashboard/ products/ movements/ requests/
│   │   │   ├── locations/ units/ categories/ reports/ users/ settings/
│   │   │   └── billing/                 [P2]
│   │   └── api/                         ROUTE HANDLERS = the REST API
│   │       ├── auth/ users/ locations/ units/ categories/
│   │       ├── products/ movements/ requests/ reports/ dashboard/
│   │       ├── daily/ audit/ health/ and billing/ [P2]
│   ├── components/ui/                   Button, Input, Table, Sheet, StatTile…
│   ├── components/layout/               DesktopShell, MobileShell
│   ├── features/                        mirrors api modules (client hooks + views)
│   ├── hooks/                           useAuth, useBusiness, usePolling, useI18n
│   ├── lib/
│   │   ├── db/                          drizzle client, schema/, migrations/
│   │   │   ├── schema/                  one file per table group
│   │   │   ├── tenant.ts  ★             withBusinessContext(businessId, fn)
│   │   │   └── index.ts                 pool + drizzle instance
│   │   ├── server/                      SERVICE layer (business rules) per module
│   │   │   ├── movements/  ★ the critical service
│   │   │   ├── requests/   ★ the workflow service
│   │   │   └── auth/ users/ locations/ …
│   │   ├── api/                         handler helpers: session, requireRole, validate, envelope
│   │   ├── schemas/                     Zod: request/response shapes (SHARED)
│   │   ├── permissions.ts               role → capability map (SHARED)
│   │   ├── units.ts                     unit/decimal rules (SHARED)
│   │   ├── money.ts / quantity.ts / dates.ts
│   │   └── i18n/                        next-intl config; en.json, rw.json
│   └── worker/                          dailyReport.ts, cleanup.ts, subscriptions.ts [P2]
├── drizzle/                             generated migration SQL
├── scripts/                             seed.ts, seed-perf.ts, import-opening-stock.ts
├── e2e/                                 Playwright
├── docker-compose.yml                   Postgres 16 (from NT)
├── docs/architecture/                        these documents
└── package.json
```

**Module anatomy (per feature).** `schema` (Zod) → `repository` (Drizzle SQL only, takes an
optional tx) → `service` (all rules, owns transactions, throws typed domain errors) → the route
handler (parse → guard → call service → map envelope). Handlers never contain business logic;
services never build presentation; repositories never decide authorization. A reviewer who sees a
rule in a handler or an auth check in a repository rejects the PR.

---

## 5. Request lifecycle (route handler)

```
Request
 ├─▶ 1. requestId + business-aware logging
 ├─▶ 2. security headers, HSTS, strict CSP (next.config + middleware)
 ├─▶ 3. rate limit (per IP; per email on sign-in; per user on the API)
 ├─▶ 4. body parse (100 KB cap)
 ├─▶ 5. session load → user + businessId (+ assigned shop for sellers)
 ├─▶ 6. CSRF check on state-changing methods
 ├─▶ 7. requireAuth → 401 if no valid session
 ├─▶ 8. ★ BUSINESS CONTEXT: BEGIN; SET LOCAL app.business_id = <session>
 ├─▶ 9. subscriptionGuard [P2] → 402 if suspended/cancelled, on writes
 ├─▶ 10. requireRole(capability) → 403 (permission matrix)
 ├─▶ 11. validate (Zod) → 422 with field errors
 ├─▶ 12. service runs business rules inside the already-open transaction
 ├─▶ 13. serialise (explicit mapping, never a raw row)
 ├─▶ 14. audit if the action is auditable
 └─▶ 15. COMMIT, log, respond (success envelope)
        │  error at any step → rollback → typed domain error → status + safe message
        ▼  (full detail to logs only; never a stack trace in the response)
```

**Envelope (from NT, kept).** Success `{ "success": true, "data": …, "message"?: "localized" }`.
Error `{ "success": false, "message": "localized", "errors"?: { field: [..] }, "code"?, "requestId" }`.
Domain error example: `code: "INSUFFICIENT_STOCK"`, `message: "Only 6 bags available in Stock 1."`,
`details: { locationName, available }`. Full code→status map in [04-api §1](./04-api-specification.md).

---

## 6. Tenancy & shop privacy — the isolation guarantee

Two composed layers (merge decision C2):

**Layer 1 — Postgres RLS (the guarantee).** Every tenant-owned table:
```sql
ALTER TABLE products ENABLE ROW LEVEL SECURITY;
ALTER TABLE products FORCE  ROW LEVEL SECURITY;
CREATE POLICY products_tenant ON products
  USING      (business_id = current_setting('app.business_id', true)::uuid)
  WITH CHECK (business_id = current_setting('app.business_id', true)::uuid);
```
The app connects as `sf_app` (no `BYPASSRLS`). Context is set per request:
```ts
// lib/db/tenant.ts
export async function withBusinessContext<T>(businessId: string, fn: (tx: Tx) => Promise<T>) {
  return db.transaction(async (tx) => {
    // SET LOCAL scopes to THIS transaction — a pooled connection cannot leak context.
    await tx.execute(sql`SELECT set_config('app.business_id', ${businessId}, true)`);
    return fn(tx);
  });
}
```
`current_setting('app.business_id', true)` is `NULL` when unset ⇒ the policy matches nothing ⇒ a
context-less request reads **zero** rows (an empty screen, never a breach).

**Layer 2 — app-layer scope helper (the review aid, from NT's global scope).** Repositories also
filter by `business_id` explicitly and, for shop-owned reads by a `seller`, by `shop_id`. Not
because RLS needs it, but because a query that reads correctly in review is easier to trust. If the
two ever disagree, **the policy wins and the repository has a bug**.

**Shop-level privacy (NT's `ShopScope`, generalized).** The session carries the user's
`assignedShopId` and a `seesAllShops` flag (`true` for owner/admin/stock_manager). For shop-owned
reads (a seller's product availability, movements, requests), a `seller` is constrained to their
`shop_id`; company-wide roles see all. Implemented as a repository helper `shopFilter(session)` and
set alongside the business context. A `seller` requesting another shop's resource gets **404**.

**Roles.** `sf_migrate` owns schema/DDL (migrations only). `sf_app` — SELECT+INSERT on tenant
tables, no BYPASSRLS, **no UPDATE/DELETE on `movements` or `audit_log`**. `sf_reversal` — as
`sf_app` plus the single UPDATE the reversal path needs on `movements`. `sf_readonly` — SELECT only.

---

## 7. The stock transaction — the most important code

Realizes FR-MOV-14/15/16. **Do not change without an ADR.**
```
recordMovement(input, session)          [business context already set — §5 step 8]
──────────────────────────────────────────────────────────────────────────────
BEGIN (READ COMMITTED)
 ├─ 1. idempotency: if (created_by, key) exists → return it                FR-MOV-16
 ├─ 2. affected locations: receipt→[to] issue→[from] transfer→[from,to] adjustment→[loc]
 ├─ 3. ★ SELECT … FROM stock_levels
 │      WHERE product_id=$1 AND location_id = ANY($2)
 │      ORDER BY location_id            ← ASCENDING, ALWAYS  (entire deadlock strategy)
 │      FOR UPDATE                                                          FR-MOV-15
 ├─ 4. validate locked rows: product & locations active · unit precision (decimals flag)
 │      deltas: receipt to:+q · issue from:−q · transfer from:−q,to:+q · adjustment: counted−current
 ├─ 5. new = current + delta; if new < 0 → throw INSUFFICIENT_STOCK, ROLLBACK   BR-02, FR-MOV-06
 ├─ 6. INSERT the movement (one row, both endpoints, both balances_after, signed_delta)
 ├─ 7. UPSERT each affected stock_levels row
 ├─ 8. audit entry if the movement is a reversal
COMMIT
──────────────────────────────────────────────────────────────────────────────
Belt & braces: stock_levels CHECK(quantity_on_hand >= 0), movements CHECK ck_mov_endpoints,
sf_app has no UPDATE/DELETE on movements. Remove step 5 by accident → the DB still refuses.
```
`stock_levels` is a **maintained projection** (ADR-005): every list/search/dashboard reads it;
recomputing from history each request would blow the perf budget. Protected by the transaction, a
`CHECK`, and the nightly reconciliation report. Reversal = the same transaction with every delta
negated; the original is checked un-reversed and its link column set via `SET LOCAL ROLE
sf_reversal` inside the tx (a half-reversal cannot exist).

---

## 8. The request workflow (PS-8)

```
POST /api/requests (seller)   validate product active, qty>0, unit precision,
                              source holds enough RIGHT NOW → 409 with every location's qty if not
                              INSERT stock_requests (status=pending, to_location=seller's shop)
GET  /api/requests?status=pending (stock manager, polled 30 s)
                              each row carries availableAtSource, recomputed at read time
POST /api/requests/:id/accept (stock manager)  status=accepted — no stock moves yet
POST /api/requests/:id/fulfil (stock manager)  ← the important one
   BEGIN
     1. SELECT the request FOR UPDATE                     prevents double fulfilment
     2. status must be pending|accepted else 409 REQUEST_NOT_PENDING
     3. recordMovement(transfer, source → seller's shop, qty)   §7, same locks
        └ INSUFFICIENT_STOCK → ROLLBACK, status back to pending, error names current availability
     4. UPDATE request: fulfilled, quantity_fulfilled, resolved_by, fulfilled_movement_id
   COMMIT → seller's next poll shows it fulfilled + updated shop quantity   FR-REQ-12/13
```
Request + movement are **one transaction** (a crash between them would move stock with no record,
or mark fulfilled with nothing moved). The unique constraint on `fulfilled_movement_id` means a
retry cannot create a second transfer. `availableAtSource` is recomputed on read, never stored
(availability at request time ≠ at fulfilment time, BR-16).

---

## 9. Frontend design (from NT + SF-DOC-04 §8)

- **State:** server data in TanStack Query (keyed by resource+filters); URL state in Next.js
  search params (filters/sort/page/report); local UI in `useState`; session+business in an
  `AuthContext` from `GET /api/auth/me`. No global store.
- **Polling:** the request queue is the only polled query (`refetchInterval: 30_000`, stops when
  the tab is hidden); the interval is a single shared constant.
- **Two shells, one codebase:** `DesktopShell` (sidebar) for admin/owner, `MobileShell` (bottom
  nav + full-screen sheets) for seller/stock manager. Chosen by **viewport, not role**; role only
  decides what appears inside. Server Components render read-heavy pages; Client Components own the
  interactive movement/request flows.
- **i18n:** next-intl; no hard-coded user-facing string; `en.json`/`rw.json` key sets must match
  (CI); RW may temporarily mirror EN with a `TODO-RW:` marker — never machine-translated. Business
  data (product/unit/location names) is never translated.

---

## 10. Security architecture (SF-DOC-04 §9 + NT)

HTTPS+HSTS · opaque session id in HttpOnly/Secure/SameSite=Strict cookie, session row carries the
business · deleting the row = instant revocation · argon2id · `requireRole(capability)` against the
shared permission map · **RLS + no BYPASSRLS + SET LOCAL** · unknown-to-you resources return 404
not 403 · Zod at every boundary · parameterised queries only (a lint rule forbids raw
string-interpolated SQL) · React escaping, no `dangerouslySetInnerHTML`, strict CSP · double-submit
CSRF + SameSite · rate limits (per IP / per email / per user / per IP on TIN lookup) · secrets from
env, gitleaks in CI · **no card data** (provider hosted checkout) · webhooks signature-verified,
provider event id stored unique for idempotency · append-only audit.

---

## 11. Scheduled work

One worker process, separate from the request path. **Daily report** every 15 min (generates for
any business whose local report time has passed with no report for that date; idempotent via an
advisory lock per business/date). **Session & token cleanup** hourly. **Reconciliation check**
nightly per business (alerts on any non-empty result; guards BR-01). **Subscription sweep** `[P2]`
hourly (`past_due → suspended` after grace; renewal reminders). **Low-stock digest** daily if
recipients configured.

---

## 12. Architecture decision records

| ADR | Decision | Rationale |
|---|---|---|
| ADR-001 | TypeScript everywhere, strict | Shared shapes; compile-time safety |
| ADR-002 | PostgreSQL | RLS, exact `numeric`, `FOR UPDATE` — all load-bearing |
| ADR-003 | Session cookies, not tokens | Instant revocation; session carries the business |
| ADR-004 | Row locks, not optimistic retry | Explicit, reviewable, low contention |
| ADR-005 | `stock_levels` maintained projection | Read performance; protected by tx + CHECK + reconciliation |
| ADR-006 | Movements immutable; reversal corrections | Audit integrity |
| ADR-007 | Shared Zod schemas | Client/server validation cannot drift |
| ADR-008 | Single-level categories | Client requirement |
| ADR-009 | No global state library | Server cache + URL cover every case |
| ADR-010 | Weighted-average costing | Explainable without batch tracking |
| ADR-011 | Idempotency keys on stock writes | Mobile networks retry |
| ADR-012 | Shared schema with RLS (not schema/DB-per-tenant) | Engine isolation, one migration path |
| ADR-013 | `stock_levels` per product per location; no quantity on products | PS-7 |
| ADR-014 | One movement row per physical event, carrying from & to | A transfer is one event |
| ADR-015 | 30 s polling, not WebSockets | Meets FR-REQ-13 at far lower cost |
| ADR-016 | Payment provider behind an adapter | Provider choice may change |
| **ADR-101** | **Next.js full-stack, one codebase (was: separate api/web)** | Merge decision C1: shared schemas, single deploy |
| **ADR-102** | **Drizzle ORM + drizzle-kit (was: pg/Kysely / Eloquent)** | RLS + explicit tx + `FOR UPDATE`; typed SQL |
| **ADR-103** | **App-layer scope helper + RLS (was: either/or)** | Merge decision C2: defense in depth |
| **ADR-104** | **Many shops per business + shop-level privacy** | Merge decision C3/C4: multi-outlet product; NT's ShopScope generalized |
| **ADR-105** | **UUID v7 PKs (was: bigint in NT)** | Not guessable; NT sanctioned the revisit |
| **ADR-106** | **`business` terminology (was: `company` in NT)** | SF-DOC is the richer domain spec |
