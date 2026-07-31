# 03 — Database Design

PostgreSQL 16. Adapts SF-DOC-05 to **Drizzle + drizzle-kit**, reconciled with the NT foundation
(`companies`→`businesses`, `shops`→`locations` of `kind='shop'`, many shops, shop-level privacy).

---

## 1. Design rules (a migration that breaks one is not merged)

1. **Primary keys are UUID v7**, generated in the app (time-ordered, not guessable in a URL).
2. `snake_case`, plural table names.
3. Every table has `created_at`; every mutable table also `updated_at` — `timestamptz`, default
   `now()`, stored UTC.
4. **Money is `bigint` minor units.** Never float, never numeric for money.
5. **Quantities are `numeric(14,3)`** — exact, 3 places for units that allow them.
6. **Deactivate, never delete**, anything referenced by history (soft delete / `is_active`).
7. **Constraints in the database**, not only in code — every invariant expressible as `NOT NULL`,
   `UNIQUE`, `CHECK`, or FK is one.
8. Case-insensitive uniqueness = a unique index on `lower(column)`.
9. No `ON DELETE CASCADE` where history lives (deleting a product with movements must fail loudly);
   CASCADE only for `sessions` and reset tokens.
10. Enums are PG `ENUM` only where the spec fixes the set (role, movement type, location kind,
    request status, subscription status). Units and reason codes are **tables** (businesses extend
    them).
11. **Every tenant-owned table carries `business_id uuid NOT NULL` and has an RLS policy. No
    exceptions.** This is the rule that, broken once, ends the product.
12. Every index on a tenant-owned table **leads with `business_id`**.
13. The app connects as a role **without `BYPASSRLS`**; migrations use `sf_migrate`.

---

## 2. Entity model

```
                       ┌───────────────────────────────┐
   plans [P2] ─────────│ businesses          ★ TENANT  │
   subscriptions [P2]  │ id · name · tin · tin_norm(UQ) │
   payments [P2]       │ country · currency · minor     │
                       │ timezone · status             │
                       └──────────────┬────────────────┘
        (every table below scoped by business_id + RLS)
   ┌──────────┬──────────┬───────────┬────────────┬──────────────┐
   ▼          ▼          ▼           ▼            ▼              ▼
 users     units    categories   locations   movement_reasons  business_settings
   │                                │  (kind: stock_room | shop; MANY of each)
   │ assigned_shop_id ──────────────┘
   ▼
 sessions / password_reset_tokens
                       ┌───────────────────────────────┐
   products ──────────│ NO quantity column (PS-7)      │
     │                └───────────────┬───────────────┘
     ▼                                ▼
 stock_levels (qty PER product PER location, CHECK ≥ 0, maintained projection)
     ▼
 movements (APPEND ONLY: receipt|transfer|issue|adjustment; from/to; balances; signed_delta;
            reverses↔reversed_by; idempotency_key)
     ▼
 stock_requests (pending|accepted|fulfilled|rejected|cancelled; from_location→to_location(shop);
                 requested_by · resolved_by · fulfilled_movement_id)
 daily_reports · audit_log
```

**Shop-owned tables** (`stock_levels`, `movements`, `stock_requests` where a `shop` is involved)
also honor shop-level privacy: a `seller`'s reads are constrained to their `assigned_shop_id` at
the application layer (§6). Stock managers/admins/owner see all.

---

## 3. Multi-tenancy — the isolation guarantee

Same as SF-DOC-05 §3. Every tenant table:
```sql
ALTER TABLE <t> ENABLE ROW LEVEL SECURITY;
ALTER TABLE <t> FORCE  ROW LEVEL SECURITY;
CREATE POLICY <t>_tenant ON <t>
  USING      (business_id = current_setting('app.business_id', true)::uuid)
  WITH CHECK (business_id = current_setting('app.business_id', true)::uuid);
```
Context is set per request with `SET LOCAL app.business_id` inside the transaction (never plain
`SET`; never from the request body). No context ⇒ `NULL` ⇒ zero rows. Roles: `sf_migrate` (DDL),
`sf_app` (SELECT+INSERT, no BYPASSRLS, no UPDATE/DELETE on `movements`/`audit_log`), `sf_reversal`
(+ the reversal UPDATE on `movements`), `sf_readonly` (SELECT).

**CI guard:** a migration test enumerates every table with a `business_id` column and asserts each
has RLS enabled, forced, and ≥1 policy. A new tenant table without them fails CI.

---

## 4. Table specifications

Only deltas from SF-DOC-05 §4 are spelled out; everything else carries over. Types shown as
Postgres; Drizzle definitions live in `src/lib/db/schema/`.

### 4.1 `businesses` (tenant root — NOT tenant-scoped, no RLS; access by PK from the session)
`id uuid PK` · `name text (2–160)` · `tin text` · `tin_normalised text UNIQUE`
(`lower(regexp_replace(tin,'[\s-]','','g'))`, maintained by trigger) · `country_code char(2)` ·
`currency_code char(3)` · `currency_minor_units smallint DEFAULT 2 (0–4)` · `timezone text DEFAULT
'Africa/Kigali'` · `status biz_status DEFAULT 'pending_payment'` · timestamps.
*(Renamed from NT `companies`; `tin` widened from strict 9-digit to normalized + platform-unique,
with a default Rwanda 9-digit **application** validation — merge decision C8.)*

### 4.2 `users`
`id` · `business_id FK` · `full_name (2–120)` · `email` (unique on `(business_id, lower(email))`) ·
`password_hash` (argon2id) · `role user_role` (`owner|admin|stock_manager|seller`) ·
`assigned_shop_id uuid NULL FK→locations` (**required for `seller`, null otherwise**;
`nullOnDelete`) · `is_active` · `must_change_password` · `failed_login_count` · `locked_until` ·
`last_login_at` · timestamps. Join table `user_locations` assigns a stock manager to locations
(empty = all). *(NT's `assigned_shop_id` kept; `assigned_stock_id` dropped — stock managers use
`user_locations`.)*

### 4.3 `locations` (merges NT `shops` + SF-DOC stock rooms)
`id` · `business_id FK` · `name` (unique per business) · `code` (unique per business,
`^[A-Za-z0-9_-]{1,16}$`) · `kind location_kind` (`stock_room|shop`) · `is_active` · `sort_order` ·
timestamps. **Many stock rooms and many shops** allowed (merge decision C3 — the SF-DOC one-shop
partial-unique index is **not** created). At registration: one default shop + one default stock
room.

### 4.4 `units`
`id` · `business_id FK` · `code` (unique per business) · `name` · `plural_name` · `allows_decimals`
· `sort_order` · `is_active`. Seeded with the 22 defaults; `allows_decimals` true for metre,
centimetre, kilogram, gram, litre, millilitre.

### 4.5 `categories`
`id` · `business_id FK` · `name` (unique per business) · `description` · `is_active`. Flat.

### 4.6 `products`
`id` · `business_id FK` · `code` (unique per business, `^[A-Za-z0-9_-]{1,32}$`, immutable once it
has movements) · `name (1–160)` · `description (≤1000)` · `category_id NULL FK` · `unit_id FK` ·
`unit_cost_minor bigint DEFAULT 0 (≥0)` · `reorder_level numeric(14,3) DEFAULT 0 (≥0)` ·
`is_active` · `created_by FK`. **No quantity column.** Indexes: unique `(business_id, lower(code))`;
GIN trigram on `name` and `code`; `(business_id, is_active, name)`; `(business_id, category_id,
is_active)`.

### 4.7 `stock_levels` (answers "where is it")
`business_id FK` · `product_id FK` · `location_id FK` · `quantity_on_hand numeric(14,3) DEFAULT 0
CHECK (>= 0)` · `updated_at`. **PK `(product_id, location_id)`.** Index `(business_id,
location_id)`; partial `WHERE quantity_on_hand > 0`. The `CHECK (>= 0)` is load-bearing (last
defence for BR-02) — do not drop to make a test pass. A missing row means zero.

### 4.8 `movement_reasons` (per-business, extensible)
`id` · `business_id FK` · `code` (`^[a-z_]{2,40}$`, unique per business) · `label` ·
`movement_type movement_type` · `requires_note` · `is_active`. Seeded: receipt (Purchase, Customer
return, Opening balance, Found stock*); transfer (Seller request, Restock shop, Rebalance, Return
to store); issue (Sale, Internal use, Damage*, Loss/theft*, Expired, Return to supplier);
adjustment (Stock count, Data correction*). *=requires note.

### 4.9 `movements` (APPEND ONLY)
`id` · `business_id FK` · `product_id FK` · `movement_type` · `from_location_id NULL FK` ·
`to_location_id NULL FK` · `quantity numeric(14,3) CHECK (>0)` · `from_balance_after` /
`to_balance_after` (≥0) · `counted_quantity` (adjustments only) · **`signed_delta numeric(14,3)`**
(computed in the service; makes reconciliation a plain sum) · `unit_cost_minor_at_time bigint` ·
`reason_id FK` · `movement_date date` · `note (≤500)` · `created_by FK` · `created_at` (no
`updated_at`) · `idempotency_key` (unique on `(created_by, idempotency_key)`) ·
`supplier_ref text NULL (≤120)` *(merge decision C4 — receipts only)* · `reverses_movement_id NULL
FK` · `reversed_by_movement_id NULL UNIQUE FK`. Table checks:
```sql
CHECK ck_mov_endpoints:  receipt(from NULL,to NOT NULL) | issue(from NOT NULL,to NULL)
  | transfer(both, from<>to) | adjustment(both equal)
CHECK ck_mov_counted:    adjustment ⇔ counted_quantity NOT NULL
CHECK ck_mov_date_window: movement_date within [created−30d, created] (backdate ≤30, no future)
CHECK ck_mov_no_self_reverse: reverses_movement_id IS NULL OR <> id
```
Indexes: `(business_id, product_id, movement_date DESC, created_at DESC)`; `(business_id,
movement_date DESC)`; `(business_id, from_location_id)`; `(business_id, to_location_id)`;
`(business_id, created_by, created_at DESC)`; the two unique partials. **Immutability three ways:**
`REVOKE UPDATE, DELETE … FROM sf_app`, a `BEFORE UPDATE OR DELETE` trigger that raises (exempting
`sf_reversal` for the reversal link UPDATE), and no endpoint.

### 4.10 `stock_requests` (the workflow — PS-8)
`id` · `business_id FK` · `product_id FK` · `from_location_id FK` (source, suggested/chosen) ·
`to_location_id FK` (**the seller's shop**) · `quantity_requested (>0)` · `quantity_fulfilled (≥0,
may be less)` · `status request_status DEFAULT 'pending'` · `note (≤500, required on partial)` ·
`reject_reason (required when rejected)` · `requested_by FK` · `accepted_by FK` · `resolved_by FK`
· `fulfilled_movement_id NULL UNIQUE FK` · `created_at`/`accepted_at`/`resolved_at`. State-machine
checks (`ck_req_fulfilled`, `ck_req_rejected`, `ck_req_partial_note`) as SF-DOC-05 §4.9. Hottest
index: `(business_id, status, created_at DESC)`; also `(business_id, from_location_id, status)`,
`(business_id, requested_by, created_at DESC)`. Never deleted; UPDATE allowed, DELETE revoked.

### 4.11 `daily_reports` · `audit_log` · `business_settings` · `sessions` · `password_reset_tokens`
- `daily_reports`: `business_id` · `report_date` (unique per business) · `payload jsonb` (computed
  at generation) · `generated_at` · `sent_at` · `send_error`.
- `audit_log`: append-only; `business_id` · `actor_user_id` · `action` · `entity` · `old`/`new
  jsonb` · `ip` · `created_at`; indexes lead with `business_id`.
- `business_settings`: one row/business; `daily_report_time time DEFAULT '18:00'` ·
  `daily_report_recipients text[]` · `low_stock_recipients text[]` ·
  `adjustment_exception_threshold numeric(14,3)` · `backdate_days smallint DEFAULT 30`.
- `sessions`: `id` (opaque token id) · `user_id FK CASCADE` · `business_id` · `expires_at` ·
  `idle_expires_at` · `last_seen_at` · `ip` · `user_agent`. Deleting the row = revocation.
- `password_reset_tokens`: single-use, 60-min expiry, CASCADE.

### 4.12 Billing `[P2]`
`plans` (NOT tenant-owned): `code PK` · `name` · `price_minor bigint` · `currency_code` · `period
(monthly|yearly)` · `max_users`/`max_locations`/`max_products` · `is_active`. `subscriptions`:
`business_id UNIQUE` · `plan_code` · `status subscription_status` · `current_period_start/end` ·
`provider` · `provider_ref` · `cancel_at_period_end` · `trial_ends_at`. `payments`:
`subscription_id` · `amount_minor` · `currency_code` · `status` · `provider_ref UNIQUE` (makes
webhook replay idempotent) · `paid_at` · `failure_reason` · `raw_event jsonb`. **No card-data
columns exist, and none may be added.**

---

## 5. Migrations (drizzle-kit; ordered)

Drizzle schema lives in `src/lib/db/schema/*`; `drizzle-kit generate` emits SQL to `drizzle/`.
Enums, the RLS/roles scaffolding, triggers, and `GRANT`/`REVOKE`/`CREATE POLICY` statements that
Drizzle doesn't model are appended as **raw SQL** in `drizzle/` (hand-written, reviewed).

| # | Migration | Contents |
|---|---|---|
| 001 | **tenancy, identity, RLS scaffolding** `[P1]` | roles (`sf_migrate/sf_app/sf_reversal/sf_readonly`), enums, `businesses` (+ TIN trigger), `users`, `sessions`, `password_reset_tokens`, `audit_log`, `business_settings`. RLS enabled+forced+policy on every tenant table. |
| 002 | **catalogue** `[P1]` | `locations`, `units`, `categories`, `products`, `movement_reasons`, `user_locations`. RLS on each. |
| 003 | **stock engine** `[P1]` | `stock_levels`, `movements` (+ append-only trigger, `REVOKE`), `stock_requests`. RLS on each. |
| 004 | **billing** `[P2]` | `plans`, `subscriptions`, `payments`. |

**Migration 001 is the one that matters** — tenancy cannot be retrofitted. Every subsequent tenant
table repeats the five RLS lines; the CI guard (§3) enforces it. The deterministic transfer lock
(`ORDER BY location_id … FOR UPDATE`) is documented in [02 §7](./02-system-architecture.md#7-the-stock-transaction--the-most-important-code)
and tested by FR-MOV-15 (50 opposing concurrent transfers).

---

## 6. Application-layer scope helpers (Drizzle)

RLS is the guarantee; these keep leaks obvious in review and enforce shop privacy:
```ts
// every tenant read still filters explicitly (belt & braces)
where(and(eq(products.businessId, ctx.businessId), /* … */))

// shop-owned reads by a seller are additionally shop-scoped
function shopFilter(ctx: Ctx, col: PgColumn) {
  return ctx.seesAllShops ? undefined : eq(col, ctx.assignedShopId!);
}
```
`ctx` (business id, role, `assignedShopId`, `seesAllShops`) comes from the session in the same
middleware that sets `app.business_id`.

---

## 7. Seed & test data

- `scripts/seed.ts` — **two businesses** (isolation visible from day one). Business A: 4 locations
  (Stock 1, Stock 2, Shop North, Shop South), 22 units, 8 categories, 40 products, ~300 movements
  incl. transfers, ~40 requests in every status, users covering all four roles (two sellers, each
  bound to a different shop, to exercise shop privacy). Business B: a smaller, deliberately
  different set with the **same product codes**, so a leak is obvious.
- `scripts/seed-perf.ts` — 20 businesses; the largest with 8 locations, 5 000 products, 150 000
  movements, 20 000 requests over 18 months.
- `scripts/import-opening-stock.ts` — reads a CSV with a quantity column per stock room; creates
  categories/units/products then one `receipt` per product per location (reason `opening_balance`);
  dry-run mode validates and writes nothing.

The two-business seed is **not optional** — every developer runs it, so every manual test is
implicitly an isolation test.

---

## 8. Backup, retention, tenant data rights

Managed daily snapshot + WAL, 30-day retention, separate account/region. Restore drilled before
each go-live. No partitioning below ~50M rows. Tenant export = one query per table filtered by the
business, streamed to an archive (FR-TEN-17). Tenant deletion (on written request) = export first,
then delete in dependency order in one transaction, then a platform-level log entry containing no
business data. Personal data limited to name/email/role/activity.
