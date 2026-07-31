# 00 — Merge Decisions & Reconciliation Report

**Status:** proposed — decisions marked ⚑ are open for your veto.
**Method:** each conflict evaluated on its merits; the stronger option chosen and justified.

---

## 1. What each system brought

| Dimension | STOCK pack (SF-DOC) | NT system (`NT/stockflow`) | Merged result |
|---|---|---|---|
| Maturity | Paper design, no code | Running foundation + tests | Build on merged design; reuse NT frontend |
| Backend | Node + Express + TypeScript | Laravel (PHP) + Sanctum | **Next.js route handlers (TS)** |
| Frontend | React + Vite + Tailwind | Next.js + next-intl + Tailwind | **Keep Next.js (App Router)** |
| DB | PostgreSQL 15, RLS | PostgreSQL 16, ORM global scope | **PostgreSQL 16 + RLS + scope helper** |
| Tenancy | Row-level security | Eloquent global scope | **Both (defense in depth)** |
| Shop privacy | one shop per business | shopkeeper sees only their shop | **Many shops + shop-level privacy** |
| i18n | designed, not built | built & working EN/RW | **Keep NT's (next-intl)** |
| Roles | Owner/Admin/Stock mgr/Seller | Boss/Super Admin/Stock mgr/Shopkeeper | **Unified 1:1 (below)** |
| Stock model | per-location, movements-derived | not built yet | **STOCK pack's model (crown jewel)** |
| Billing | subscription (Phase 2) | none | **STOCK pack's (Phase 2)** |
| Suppliers | out of scope | "Suppliers → Main Stock" | **Receipt movement, no supplier records ⚑** |

---

## 2. Canonical stack (decided)

| Layer | Choice | Why |
|---|---|---|
| Runtime | **Next.js 15+ (App Router), one codebase** | Your call. Frontend + API in one repo; shared validation is the payoff. |
| Language | **TypeScript** ⚑ | The single-codebase win is sharing Zod schemas & types client↔server; money/decimal/movement logic is exactly where TS prevents bugs. NT's small JS frontend is cheap to convert. |
| Database | **PostgreSQL 16** | NT already runs it via Docker; RLS + exact `numeric` + `FOR UPDATE` are all load-bearing. |
| DB access | **Drizzle ORM + drizzle-kit**, `postgres.js` driver | Raw-SQL control needed for RLS, explicit transactions, deterministic `FOR UPDATE` lock ordering. Matches SF-DOC's "hand-written typed SQL" intent. |
| Validation | **Zod** (shared `packages`/`lib/schemas`) | One schema validates the form and the request body. |
| Auth | **Session cookies** (httpOnly, Secure, SameSite=Strict) + server-side `sessions` table ⚑ | Instant revocation; the session row carries `business_id`. Replaces Sanctum (Laravel is gone). |
| Hashing | **argon2id** | Memory-hard (SF-DOC NFR-SEC-02). |
| Styling | **Tailwind CSS** (+ small UI kit) | NT already uses Tailwind v4. |
| i18n | **next-intl**, locales `en` + `rw` | NT's bilingual plumbing already works — do not rebuild. |
| Client server-state | **TanStack Query** | Caching, invalidation, and the 30 s request-queue poll. |
| PK | **UUID v7** (app-generated) ⚑ | Not guessable in a URL; index locality. NT's CLAUDE.md explicitly sanctioned revisiting bigint. |
| Scheduler | **Node worker** (or Vercel Cron / node-cron) | Daily report, session cleanup, subscription sweep. |
| Testing | **Vitest** (unit + integration), **Playwright** (e2e) | NT frontend already uses Vitest. |
| CI | **GitHub Actions** | NT already has a workflow to extend. |

---

## 3. Roles — unified 1:1

The two role sets map cleanly. Canonical machine names and the reconciliation:

| Canonical (machine) | STOCK pack | NT system | Capability summary |
|---|---|---|---|
| `owner` | Owner | Boss | Read-only; receives the daily report. |
| `admin` | Administrator | Super Administrator | Full workspace setup: products, units, locations, users, settings, subscription. |
| `stock_manager` | Stock manager | Stock Manager | Runs the stock rooms; receives goods; fulfils requests; counts; reverses own same-day movements. |
| `seller` | Seller | Shopkeeper | Shop floor; scoped to one shop; checks availability; raises requests; records sales (issues from the shop). |

A user has **exactly one** role and (for `seller`) **exactly one** assigned shop. The full
server-enforced permission matrix is in [01-requirements §3](./01-requirements.md#3-permission-matrix).

---

## 4. Locations — the reconciled place model

The two systems described the same reality differently:

- STOCK pack: "many stock rooms + **exactly one** shop" per business (BR-15) — a client-specific
  constraint.
- NT system: "one Main Stock → **many** shops" — the product vision (multi-outlet retail).

**Decision:** one `locations` table with `kind ∈ {stock_room, shop}`, **many of each allowed**.
This is more general and matches the product's multi-outlet premise. Consequences:

- The STOCK pack's "exactly one active shop" partial-unique index is **dropped**. ⚑
- **Shop-level privacy is adopted from NT** and generalized: a `seller` is assigned to one shop
  and sees only that shop's stock/movements/requests; `owner`/`admin`/`stock_manager` see all
  locations. This is the STOCK pack's per-location engine **plus** NT's `ShopScope`.
- Goods flow **supplier → stock room → shop** (NT's "Main Stock → Shops"), realized as movements:
  `receipt` into a stock room, `transfer` stock room → shop (the fulfilment of a seller request),
  `issue` out of a shop (a sale).
- The seller→stock-manager **request → approve → fulfil** workflow (NT's `request→approve→issue→
  confirm`) is the STOCK pack's `stock_requests` flow — identical concept, kept in full.

---

## 5. Conflicts resolved (each with the call)

| # | Conflict | STOCK pack | NT system | Decision | Rationale |
|---|---|---|---|---|---|
| C1 | Backend stack | Node/Express | Laravel | **Next.js route handlers (TS)** | Your call; one codebase, shared schemas. |
| C2 | Tenancy mechanism | RLS | ORM global scope | **Both** | RLS is the guarantee; the scope helper keeps leaks obvious in review. |
| C3 | Shops per business | exactly one | many | **Many** ⚑ | Product is multi-outlet retail. |
| C4 | Suppliers | out of scope | origin of goods | **No supplier records; optional free-text `supplier_ref` on receipts** ⚑ | Keeps scope deliverable (SF-DOC excludes purchase orders) while honoring NT's flow. |
| C5 | Auth transport | session cookie | Sanctum bearer | **Session cookie** ⚑ | Instant revocation; session carries tenant; one codebase makes cookies natural. |
| C6 | PK type | UUID v7 | bigint | **UUID v7** ⚑ | Not guessable; NT sanctioned revisiting. |
| C7 | Language | TypeScript | JavaScript | **TypeScript** ⚑ | Shared validation & domain safety. |
| C8 | TIN validation | normalize + platform-unique, no format check | strict 9-digit Rwanda | **Normalize + platform-unique (hard); 9-digit as a default, overridable per-country validation** ⚑ | Product targets Rwanda but is a platform; keep the hard rules, soften the format check. |
| C9 | Terminology | "business" | "company" | **`business` in code & schema** | SF-DOC is the richer spec; `business_id` is referenced everywhere in the domain design. |
| C10 | Stock precision | money=minor units, qty=`numeric(14,3)` | not built | **Adopt STOCK pack's** | No conflict; it's the correct model. |

---

## 6. What is kept verbatim from each

**From the STOCK pack (unchanged):**
- Movements are immutable; corrections are reversals; quantity is derived per location; no quantity
  column on products. (BR-01, BR-05, PS-7.)
- The deterministic-lock stock transaction and the request-fulfilment transaction (SF-DOC-04 §6–7).
- Money as integer minor units; quantities as exact decimals with a per-unit decimals flag.
- Dynamic units and reason codes as per-business data.
- The daily report; the six reports; the reconciliation check.
- The two-phase delivery split (Phase 1 stock system, Phase 2 registration + billing).
- Subscription lifecycle and the payment-provider adapter (Phase 2).

**From the NT system (kept & extended):**
- The Next.js frontend structure, `next-intl` bilingual EN/RW, the API envelope shape, the
  `ApiResponse` success/error contract.
- Shop-level privacy (`ShopVisibility` / `ShopScope`), generalized to many shops.
- TIN-based login (TIN + email + password), generic invalid-credentials message.
- Soft-delete / deactivate-never-delete discipline (aligns with SF-DOC BR-09).
- The docker-compose Postgres service.

---

## 7. Phases (reconciled)

| | Phase 1 — core stock system | Phase 2 — SaaS |
|---|---|---|
| Goal | A business runs real stock: locations, units, categories, products, movements, the request workflow, reports, the daily report. Multi-tenant + RLS from migration 001. | Public registration by TIN, plans, payment (provider adapter), subscription lifecycle, invoices. |
| Auth/users | Full auth, roles, user management | Self-service billing |
| Registration | Exists (admin-provisioned + public tenant create) | Gated behind payment; onboarding wizard |

NT already ships public company registration (`POST /companies`); in the merged plan that becomes
Phase-1 tenant creation, with **payment** layered on in Phase 2 (a workspace sits in
`pending_payment` until paid).

---

## 8. Open items needing your input (⚑)

1. **C3** — confirm **many shops** per business (vs the STOCK client's single shop).
2. **C4** — suppliers: **no supplier table**, just an optional `supplier_ref` text on receipts?
3. **C7** — confirm **TypeScript** (convert the small JS frontend).
4. **C8** — TIN: keep a **default 9-digit Rwanda check** but allow other countries later?
5. Sub-decisions C5 (session cookies), C6 (UUID v7) — confirm or veto.

Nothing downstream is blocked: the docs proceed on the recommended defaults above, and any veto is
a localized change.
