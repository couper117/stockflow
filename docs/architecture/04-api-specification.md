# 04 — API Specification

Realizes [01-requirements](./01-requirements.md) as endpoints. Implemented as **Next.js Route
Handlers** under `src/app/api/**`. Keeps NT's response envelope; adds the SF-DOC error contract.

---

## 1. Conventions

**Base path** `/api`. **Auth** via **session cookie** (httpOnly, Secure, SameSite=Strict); no
bearer token in the browser. Server-to-server (webhooks) is signature-verified, not cookie-auth.

**Success envelope**
```json
{ "success": true, "data": {}, "message": "optional localized string", "meta": { "page": 1, "pageSize": 20, "total": 137 } }
```
**Error envelope**
```json
{ "success": false, "message": "localized string", "code": "INSUFFICIENT_STOCK",
  "errors": { "field": ["localized message"] },
  "details": { "locationName": "Stock 1", "available": "6.000" },
  "requestId": "01J9F3K2R7QW" }
```

**Localization.** Messages are localized from `Accept-Language`, falling back to the user's stored
`locale`, then `en`. `Content-Language` is set on every response. Auth failures reveal nothing about
which field was wrong.

**Status codes**

| Code | Meaning |
|---|---|
| 200 / 201 / 204 | OK / created / no content |
| 400 | Malformed request / bad webhook signature |
| 401 | Not authenticated (no/expired session) |
| 402 | `SUBSCRIPTION_INACTIVE` — suspended/cancelled business, on a write `[P2]` |
| 403 | Authenticated but not permitted (permission matrix) |
| 404 | Not found **or another tenant's/shop's resource** (never 403 for cross-tenant) |
| 409 | Conflict (`INSUFFICIENT_STOCK`, `REQUEST_NOT_PENDING`, `PRODUCT_HAS_MOVEMENTS`, duplicate) |
| 422 | Validation failed (Zod) — `errors` map present |
| 429 | Rate limited |
| 500 | Unhandled (safe message only; detail to logs) |

**Domain error codes** (mapped to status + localized message):
`INVALID_CREDENTIALS` (401) · `ACCOUNT_LOCKED` (429) · `INSUFFICIENT_STOCK` (409) ·
`LOCATION_INACTIVE` (409) · `PRODUCT_HAS_MOVEMENTS` (409) · `LOCATION_HAS_STOCK` (409) ·
`UNIT_IN_USE` (409) · `CATEGORY_IN_USE` (409) · `REQUEST_NOT_PENDING` (409) ·
`ALREADY_REVERSED` (409) · `CANNOT_REVERSE_REVERSAL` (409) · `DATE_OUT_OF_WINDOW` (422) ·
`LAST_ADMIN` (409) · `SUBSCRIPTION_INACTIVE` (402) · `TIN_ALREADY_REGISTERED` (409).

**Pagination / filtering / sorting.** List endpoints accept `?page`, `?pageSize` (≤100), `?sort`,
`?order=asc|desc`, plus resource filters. Filter/sort/page state is reflected in the URL client-side
(FR-PRD-15). Every list response carries `meta` with `page/pageSize/total`.

**Idempotency.** Stock-writing endpoints accept an `Idempotency-Key` header; a repeat returns the
original result (FR-MOV-16).

**Rate limits.** 5 sign-ins/email/business/15 min · 10 TIN lookups/IP/min · 100 req/min/user.

---

## 2. Endpoints

Legend: 🔓 public · 🔒 authenticated. Role column lists who may call it (permission matrix).

### 2.1 Auth & session — `[P1]`
| Method | Path | Access | Notes |
|---|---|---|---|
| POST | `/api/auth/login` | 🔓 | `{ tin, email, password }` → sets session cookie, returns `{ user }`. Generic error on any failure. Rate-limited. |
| POST | `/api/auth/tin-lookup` | 🔓 `[P2]` | `{ tin }` → `{ businessName }` for confirmation; neutral for unknown; rate-limited 10/IP/min. |
| POST | `/api/auth/logout` | 🔒 | Deletes the current session row. |
| GET | `/api/auth/me` | 🔒 | `{ user, business, capabilities, currency, timezone, locale }`. Drives `AuthContext`. |
| POST | `/api/auth/change-password` | 🔒 | `{ currentPassword, newPassword }`; ends other sessions. |
| POST | `/api/auth/forgot-password` | 🔓 | `{ tin, email }`; always neutral response; emails a single-use 60-min link. |
| POST | `/api/auth/reset-password` | 🔓 | `{ token, newPassword }`. |

### 2.2 Businesses & registration — `[P1 create]` / `[P2 billing]`
| Method | Path | Access | Notes |
|---|---|---|---|
| POST | `/api/businesses` | 🔓 | Register: `{ name, tin, country, currency, timezone, admin:{name,email,password} }`. TIN normalized + platform-unique. Creates workspace `pending_payment` (P2) / `active` (P1 provisioning), one shop + one stock room, seeded units + reasons, one admin. Rate-limited. |
| GET | `/api/business` | 🔒 owner/admin | Current business profile + settings. |
| PATCH | `/api/business/settings` | 🔒 admin | name, currency, minor units, timezone, daily-report time & recipients, low-stock recipients, adjustment threshold, backdate days. |

### 2.3 Users — `[P1]`
| Method | Path | Access | Notes |
|---|---|---|---|
| GET | `/api/users` | 🔒 admin | Search `?q`, filter `?role`, `?status`, paginate. |
| POST | `/api/users` | 🔒 admin | `{ name, email, role, assignedShopId?, locationIds? }`; emails invite; `seller` **requires** `assignedShopId`. |
| PATCH | `/api/users/:id` | 🔒 admin | name/role/active/assignedShop/locations. Cannot self-demote/deactivate; keep ≥1 active admin → `LAST_ADMIN`. |
| POST | `/api/users/:id/reset-password` | 🔒 admin `[P2]` | Triggers a reset email; admin never sees the password. |

### 2.4 Locations — `[P1]`
| Method | Path | Access | Notes |
|---|---|---|---|
| GET | `/api/locations` | 🔒 all | Per-location distinct-product count + total quantity. `kind` filter. |
| POST | `/api/locations` | 🔒 admin | `{ name, code, kind }`. Many stock rooms & shops allowed. |
| PATCH | `/api/locations/:id` | 🔒 admin | rename/reorder/deactivate. `LOCATION_HAS_STOCK` if holding stock. |

### 2.5 Units — `[P1]`
`GET/POST /api/units`, `PATCH /api/units/:id` — 🔒 read all / write admin. Deactivate only if in
use (`UNIT_IN_USE` on delete).

### 2.6 Categories — `[P1]`
`GET/POST /api/categories`, `PATCH /api/categories/:id` — 🔒 read all / write admin.
`CATEGORY_IN_USE` on delete.

### 2.7 Products — `[P1]`
| Method | Path | Access | Notes |
|---|---|---|---|
| GET | `/api/products` | 🔒 all | Trigram search `?q`; filters `?category`, `?active`, `?location`, `?lowStock`; sort; paginate. Total + per-location breakdown. Seller sees no cost fields. |
| POST | `/api/products` | 🔒 admin | `{ code, name, categoryId?, unitId, unitCostMinor, reorderLevel, description? }`. Zero stock everywhere. |
| GET | `/api/products/:id` | 🔒 all | Quantity per location, total, stock value (not for seller), low-stock, movement history, role-appropriate actions. |
| PATCH | `/api/products/:id` | 🔒 admin | name/description/category/cost/reorder/active. Code immutable once it has movements (`PRODUCT_HAS_MOVEMENTS`, 409). Cost change audited (old→new). |

### 2.8 Movements — `[P1]` **core**
| Method | Path | Access | Notes |
|---|---|---|---|
| GET | `/api/movements` | 🔒 all | Filters: date range, product, category, location, type, reason, user; combine; totals in/out. Seller scoped to their shop. |
| POST | `/api/movements` | 🔒 admin/stock_manager (+ seller for shop issue) | `{ type, productId, quantity, fromLocationId?, toLocationId?, reason, note?, movementDate?, supplierRef? }`. Runs the stock transaction (§7 of arch). `Idempotency-Key` honored. `INSUFFICIENT_STOCK` (409) names the location. |
| POST | `/api/movements/:id/reverse` | 🔒 admin (stock_manager: own, same-day) | `{ note }` (required). Linked opposite movement; `ALREADY_REVERSED` / `CANNOT_REVERSE_REVERSAL`. |

Movement `type` semantics: `receipt` (to only), `issue` (from only), `transfer` (from≠to),
`adjustment` (counted quantity for one location; preview signed difference before saving).

### 2.9 Stock requests — `[P1]` **flagship**
| Method | Path | Access | Notes |
|---|---|---|---|
| GET | `/api/requests` | 🔒 all | `?status`, `?mine=true` (seller's own), `?location`. Stock-manager queue is polled every 30 s; each row carries `availableAtSource`. |
| POST | `/api/requests` | 🔒 seller/stock_manager/admin | `{ productId, quantity, fromLocationId? }`; `toLocation` = the seller's shop. Suggests the source holding most; `INSUFFICIENT_STOCK` (409) at creation with every location's qty. |
| POST | `/api/requests/:id/accept` | 🔒 stock_manager/admin | → `accepted`. No stock moves. |
| POST | `/api/requests/:id/fulfil` | 🔒 stock_manager/admin | `{ quantityFulfilled?, note? }`. One transaction: transfer source→shop + mark fulfilled. Partial requires note. `REQUEST_NOT_PENDING` / `INSUFFICIENT_STOCK` (returns to pending). |
| POST | `/api/requests/:id/reject` | 🔒 stock_manager/admin | `{ reason }` (required). |
| POST | `/api/requests/:id/cancel` | 🔒 requester | Only while `pending`. |

### 2.10 Dashboards — `[P1]`
`GET /api/dashboard` — 🔒 all. One call; role-shaped payload (owner/admin/stock_manager/seller).
**No cost/value fields in the stock-manager or seller payload** (omitted server-side, not hidden).

### 2.11 Reports — `[P1]`
`GET /api/reports/:type` where type ∈ `current-stock | stock-by-location | valuation | low-stock |
movements | transfers | requests | reconciliation`. 🔒 per matrix (valuation hidden from seller;
seller sees only their shop's stock report). `?format=csv` streams CSV (BOM, quoting,
formula-injection guard, dated filename). Filters in the query string; reproducible from the URL.

### 2.12 Daily report — `[P1]`
`GET /api/daily?date=YYYY-MM-DD` (owner/admin) — stored payload for any past day.
`GET /api/daily/:date/export?format=pdf|csv`.

### 2.13 Audit — `[P1]`
`GET /api/audit` — 🔒 admin (owner: stock-related entries only). Filters: actor, action, date range.

### 2.14 Health — `[P1]`
`GET /api/health` — 🔓. 200 minimal body when healthy, 503 when the DB is unreachable. No
version/config leak.

### 2.15 Billing — `[P2]`
`GET /api/plans` 🔓 · `POST /api/billing/checkout` 🔒 admin/owner · `POST /api/billing/webhook` 🔓
(signature-verified, idempotent by provider event id) · `GET /api/billing/subscription` 🔒 ·
`PATCH /api/billing/subscription` (plan change / cancel-at-period-end) · `GET /api/billing/invoices`
· `GET /api/billing/invoices/:id.pdf` · `GET /api/billing/export` (full workspace CSV archive).
Writes on suspended/cancelled businesses return **402 `SUBSCRIPTION_INACTIVE`**; reads/exports
continue.

---

## 3. Validation & schemas

Every request body and query is validated by a **Zod schema in `src/lib/schemas/`**, imported by
both the route handler and the matching client form (single source of truth). A 422 returns the Zod
issues mapped into the `errors` object with **localized** messages. Server-side validation is
authoritative; client validation is a convenience.

## 4. Cross-cutting handler helpers (`src/lib/api/`)
`withHandler(config, fn)` composes: rate limit → parse → session load → CSRF → `requireAuth` →
`withBusinessContext` (opens the tx, sets `app.business_id`, sets shop ctx) → `subscriptionGuard`
`[P2]` → `requireRole(capability)` → `validate(schema)` → `fn(ctx, input, tx)` → serialise → audit
→ commit. A thrown typed domain error is mapped to `{ status, code, message, details }`.
