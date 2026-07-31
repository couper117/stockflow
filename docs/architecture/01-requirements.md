# 01 — Software Requirements (Merged SRS)

Reconciles SF-DOC-03 with the NT foundation. IDs are preserved from the STOCK pack so tests and
tickets can point at them. Priority: **Must** / **Should** / **Could**. Phase: **P1** (core stock)
/ **P2** (SaaS billing).

---

## 1. Product summary

StockFlow is a **multi-business (multi-tenant) stock management web app**, sold as a subscription,
for small/local retailers (initial market: Rwanda). One deployment serves many businesses, each in
a completely isolated workspace. Goods flow **supplier → stock room(s) → shop(s)**. It is **stock
only** — not a POS, not a warehouse/logistics system, not accounting. Mobile-first, dark-mode
first-class, fully bilingual EN + RW.

It answers eight problems (PS-1…PS-8 in SF-DOC-01); the two that shape the design:
- **PS-7** — stock lives in multiple places; quantity is held **per location**, never as one number.
- **PS-8** — the seller↔stock-manager workflow: a seller sees availability by location, raises a
  request, the stock manager fulfils it as a recorded transfer, and the goods then show in the shop.

---

## 2. Actors & user classes

| Role | Device | Frequency | Description |
|---|---|---|---|
| `owner` | Desktop, sometimes phone | Daily, briefly | Accountable for stock value. **Read-only.** Receives the daily report. |
| `admin` | Computer | Daily→weekly | Sets up the workspace: products, units, categories, locations, users, settings, subscription. |
| `stock_manager` | Phone/tablet, on their feet | Many times/day | Runs stock rooms; receives goods; fulfils requests; counts; records damage. |
| `seller` | Phone, almost exclusively | Constantly | Shop floor; **assigned to one shop, sees only it**; checks availability; requests stock; records sales. |

`seller` and `stock_manager` drive the design; their two journeys — *find it & ask for it* and
*bring it & confirm* — must be the fastest paths in the system.

---

## 3. Permission matrix

Server-enforced on every request, independent of the UI. `●` full · `◐` limited (see note) · `○` none.

| Capability | owner | admin | stock_manager | seller |
|---|:--:|:--:|:--:|:--:|
| Sign in, change own password | ● | ● | ● | ● |
| View dashboard | ● | ● | ◐¹ | ◐² |
| View products & stock by location | ● | ● | ● | ◐⁷ |
| See unit cost / stock value / valuation | ● | ● | ◐³ | ○ |
| Create/edit/deactivate product | ○ | ● | ○ | ○ |
| Create/edit/deactivate category | ○ | ● | ○ | ○ |
| Create/edit/deactivate unit | ○ | ● | ○ | ○ |
| Create/edit/deactivate location | ○ | ● | ○ | ○ |
| Record receipt (goods in) | ○ | ● | ● | ○ |
| Record transfer between locations | ○ | ● | ● | ○ |
| Fulfil a stock request | ○ | ● | ● | ○ |
| Record issue from the shop | ○ | ● | ● | ●⁷ |
| Record issue from a stock room | ○ | ● | ● | ○ |
| Record adjustment after a count | ○ | ● | ● | ○ |
| Reverse a movement | ○ | ● | ◐⁴ | ○ |
| Raise a stock request | ○ | ● | ● | ●⁷ |
| Cancel own pending request | ○ | ● | ● | ● |
| Accept/reject a stock request | ○ | ● | ● | ○ |
| View movement history | ● | ● | ● | ◐⁵ |
| View reports | ● | ● | ◐³ | ◐⁶ |
| Export reports to CSV | ● | ● | ○ | ○ |
| Receive / view the daily report | ● | ● | ○ | ○ |
| Create/edit/deactivate users | ○ | ● | ○ | ○ |
| View audit log | ◐⁸ | ● | ○ | ○ |
| Manage subscription, pay, invoices `[P2]` | ● | ● | ○ | ○ |
| Edit business settings | ○ | ● | ○ | ○ |

**Notes.** ¹ Stock-manager dashboard: pending requests first, then low stock in their locations,
then today's movements; no valuation. ² Seller dashboard: own requests, what's in **their** shop,
what's low there; **no cost figures anywhere**. ³ Stock manager sees quantities and stock reports,
not cost/valuation. ⁴ Stock manager may reverse only a movement **they** created, on the **same
calendar day**. ⁵ Seller sees movements for **their** shop plus their own requests. ⁶ Seller sees
the shop stock report for **their** shop only. ⁷ Seller is constrained to their assigned shop
(shop-level privacy). ⁸ Owner sees stock-related audit entries, not user-management/security ones.

- **FR-SEC-01 (Must, P1)** — every `○` cell returns HTTP **403** and changes nothing, proven by a
  generated role-matrix suite covering every cell.
- **FR-SEC-02 (Must, P1)** — every request is scoped to the caller's business; no endpoint accepts
  a business id as a parameter; another business's resource id returns **404** (not 403).
- **FR-SEC-02b (Must, P1)** — a `seller`'s reads/writes are additionally scoped to their assigned
  shop; another shop's resource returns **404** for that seller.

---

## 4. Functional requirements by module

Acceptance criteria carry over from SF-DOC-03 (§4) unchanged unless noted. This section lists the
requirement set; see the STOCK pack SF-DOC-03 for the full per-requirement acceptance clauses.

### 4.1 Tenancy, registration, subscription (TEN)
- **FR-TEN-01/02 (Must P1)** — every tenant row carries `business_id`; the **database** refuses
  cross-business reads (RLS); every request sets business context from the session before any data
  access; no context ⇒ zero rows.
- **FR-TEN-03 (Must P1)** — a business holds name, TIN, country, currency, currency minor units,
  timezone, status.
- **FR-TEN-04…18 (P2)** — public registration by TIN; TIN unique across platform (normalized:
  strip spaces/hyphens, case-insensitive); TIN lookup on sign-in shows the business name; plan
  catalogue; payment via provider adapter (no card data stored); signature-verified idempotent
  webhooks; states `pending_payment → trialing → active → past_due → suspended → cancelled`;
  suspended/cancelled = read-only, data intact; failed renewal → `past_due` + 7-day grace; plan
  change / cancel-at-period-end; PDF invoices; full CSV workspace export; optional free trial (off
  by default).

### 4.2 Authentication (AUTH) — P1
Sign in with **TIN + email + password** (Phase 1 TIN pre-filled); generic invalid-credentials
message; email unique **within a business**; argon2id hashing; sessions expire 12 h absolute /
60 min idle; admin-created user must set password at first sign-in; email password reset
(single-use, 60 min); change own password ends other sessions; lockout after 5 failed attempts /
15 min; deactivated user's sessions die immediately; password rules (≥10 chars, not top-1000, not
equal to email).

### 4.3 User management (USR) — P1
Admin creates users (name, email, role, active, **assigned shop** for sellers) with emailed
invite; edit name/role/active/shop; deactivate never delete; cannot self-demote/deactivate; a
business always keeps ≥1 active admin; searchable/filterable list; a `stock_manager` may be
assigned to one or more locations (default all). `seller` **must** have exactly one assigned shop.

### 4.4 Locations (LOC) — P1
Location = name, short code, `kind ∈ {stock_room, shop}`, active flag, sort order. **Many stock
rooms and many shops** per business (merged decision C3). Names & codes unique per business
(case-insensitive). Create/rename/deactivate; a location holding stock cannot be deactivated; a
location referenced by any movement cannot be deleted; deactivated locations vanish from selectors
but remain in history; the list shows per-location distinct-product count and total quantity.
The first shop and a default stock room are created at registration.

### 4.5 Units of measure (UOM) — P1
Units are **per-business data**, not a fixed list. Code, singular, plural, `allows_decimals`,
active. Seeded with 22 defaults (piece, box, carton, pack, set, roll, metre, centimetre, kilogram,
gram, litre, millilitre, bundle, pair, dozen, sheet, bag, sack, tin, bottle, crate, tray).
`allows_decimals` governs precision (off = whole numbers, on = up to 3 dp). A unit in use cannot be
deleted; a product's unit cannot change once it has movements.

### 4.6 Categories (CAT) — P1
Single flat level; name unique per business; rename propagates; deactivate keeps it on existing
products; a category with products cannot be deleted; products may be uncategorised.

### 4.7 Products (PRD) — P1
Product = **code** (the product number, 1–32 chars, unique per business, immutable once it has
movements), name, category, unit, unit cost (minor units), reorder level (total across locations),
description, active. **No quantity field** — quantity is held per location and derived from
movements. Trigram search on code+name; filter by category/active/location/low-stock; sort;
filter+sort+page state in the URL; detail shows quantity per location, total, stock value,
low-stock state, and movement history; role-appropriate pre-filled actions.

### 4.8 Stock movements (MOV) — P1 · **the core**
Four types: `receipt` (into a location), `transfer` (between two), `issue` (out of one),
`adjustment` (sets one location to a counted figure). Quantity always > 0; direction from
source/destination. A transfer moves quantity from one location to another in **one recorded
event / one transaction**. Any movement taking a location below zero is refused (`INSUFFICIENT_STOCK`,
nothing written). Reason required (from the business's list for that type); note required for
flagged reasons (Damage, Loss/theft, Found stock, Data correction). Backdate up to 30 days, never
future. **Immutable** — no edit/delete; correction is a linked **reversal** (once only, a reversal
can't be reversed). A location's quantity always equals the signed sum of movement effects there
(reconciliation = zero). **Concurrency-safe** (deterministic lock order; no deadlock on opposing
transfers). Idempotency key ⇒ one movement. Phone: record an issue from the shop in **≤4 taps**.
Each movement stores resulting balance(s). Optional `supplier_ref` free-text on receipts (merged
decision C4 — no supplier table).

### 4.9 Stock requests (REQ) — P1 · **the flagship workflow (PS-8)**
A seller searches by code/name and sees quantity in every location (their shop leads). Raises a
request (product, quantity, optional source). System suggests the source holding the most; refuses
a request exceeding the chosen location, stating availability. Stock manager sees pending requests
for their locations (newest first, poll ≤30 s), can **accept** (collecting) then **fulfil** —
fulfilment creates a `transfer` from source to **the seller's shop** and marks the request
fulfilled, **in one transaction**; partial fulfilment allowed with a note; fulfilment refused if
source no longer holds enough (returns to pending); reject with a required reason; seller cancels
own pending request. After fulfilment the seller's view leads with the new shop quantity. Seller
notified in-app within 30 s. Requests never deleted; full history reportable.

### 4.10 Dashboards (DSH) — P1
One per role, single API call, useful at 320 px, interactive < 2.5 s p95 on 4G. Owner: total value,
value by location, movements today, low-stock count, yesterday's report, recent activity. Admin:
owner's figures + pending requests + products with no stock + users active today. Stock manager:
pending requests first, then low stock in their locations, then today's movements (no money).
Seller: own open requests, what's low in **their** shop, a search box (no money anywhere in the
payload).

### 4.11 Reports (REP) — P1
Current stock, stock by location, valuation (subtotals per category & location; hidden from
seller), low stock, movements, transfers, requests, reconciliation. On-screen + CSV (BOM, quoting,
formula-injection guard, dated filename). Filters shown as a sentence; state in the URL; 12-month
range < 3 s p95.

### 4.12 Daily report (DAY) — P1
Generated per business at a configured local time (default 18:00, business timezone), emailed to
owners & admins, viewable in-app for any past day, downloadable. Figures computed at generation and
**stored** (a later cost change never alters a past report). Sections: closing value + change, what
came in, what went out by reason, what moved to shops, low stock, request stats, exceptions
(reversals, large adjustments, refused issues, reconciliation discrepancies), top-ten movers. Sent
even on an empty day. Failure alerts + retries.

### 4.13 Audit, settings, health (SYS) — P1
Append-only audit log of security/change events (actor, action, target, timestamp, IP, old/new).
Not editable/deletable via the app. Filterable. Business settings: name, TIN, currency, minor
units, timezone, daily-report time & recipients, low-stock recipients, adjustment-exception
threshold. Unauthenticated health endpoint (no version/config leak).

### 4.14 Cross-cutting UI (UIX) — P1
Field-level validation; confirmation for irreversible actions naming object + consequence; empty
states; loading/disabled states (no double-submit); actionable failure messages preserving entered
data; WCAG 2.1 AA + keyboard operable; **no hard-coded user-facing strings** (every string a key
in `en` + `rw`, key sets must match — CI-enforced); business data (product/category/location/unit
names) is **never translated**.

---

## 5. Non-functional (carried from SF-DOC-03 §7)
- **Perf:** dashboard < 2.5 s p95 on 4G; product+location search < 500 ms p95; movement/request
  ack < 1 s p95; 12-month report < 3 s p95; initial JS < 250 KB gz; tenant scoping < 10 % overhead;
  every tenant index leads with `business_id`.
- **Security:** HTTPS+HSTS; argon2id; server-side authorization on every request; **RLS tenant
  isolation, app role without BYPASSRLS**; Zod on every boundary; parameterized queries only; strict
  CSP, no inline script; session cookies HttpOnly/Secure/SameSite=Strict + CSRF on writes; rate
  limits (5 sign-ins/email/15 min, 10 TIN lookups/IP/min, 100 req/min/user); no stack traces in
  responses; secrets from env, secret-scan in CI; no card data.
- **Reliability:** 99.5 % target; daily backup 30-day retention; every multi-step write is one
  transaction; no permanent data loss on any user action.
- **Usability:** primary task ≤3 taps on mobile; 44×44 px touch targets; numeric keypad for
  quantities; colour never the only signal; every quantity shown **with its location**.
- **Maintainability:** tests cover every Must (≥70 %, ≥90 % for movements/requests/authz/tenancy);
  lint+format in CI; new dev to running local env < 30 min; versioned migrations with rollback;
  structured logs carrying `requestId` + `businessId`; payment provider behind an interface with a
  fake for tests.
