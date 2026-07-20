# CLAUDE.md — StockFlow

> **This file is authoritative.** Claude Code loads it every session. When a rule or
> decision changes, update it **here** first. Every future session must follow it.

---

## 1. Product summary

StockFlow is a **production-grade, multi-company (multi-tenant) Stock Management
System** for **local / small businesses in Rwanda**.

Goods move: **Suppliers → the company's MAIN STOCK → its SHOPS.** Each company signs
into its own panel using its **TIN (Tax Identification Number) + email + password**.

**Four roles:**
- **Super Administrator** — manages the company, its staff, shops, and stock.
- **Stock Manager** — runs the Main Stock.
- **Shopkeeper** — runs a single shop.
- **Boss** — read-only.

The app is **mobile-first**, **dark-mode first-class**, and **fully bilingual in
English + Kinyarwanda**. It is **NOT a logistics app** — no maps, drivers, delivery
routes, or GPS. Moving stock to a shop is a simple internal flow:
`request → approve → issue → confirm`.

---

## 2. Tech stack (decided — do NOT change without asking)

- **Monorepo** with `/frontend` and `/backend`.
- **Frontend:** Next.js (App Router) + React in **JavaScript** (not TypeScript),
  Tailwind CSS, i18n via **next-intl** (locales `en` + `rw`). Node 20+.
- **Backend:** Laravel (latest stable supporting PHP 8.2+) REST API, **PostgreSQL**,
  **Laravel Sanctum** token auth, Laravel localization (`lang/en`, `lang/rw`).
- **Multi-tenancy:** **SINGLE shared database.** Every business table has a
  `company_id`; a **global scope** enforces isolation. NOT a database-per-tenant model.

---

## 3. Architecture rules (enforce from commit #1)

- **Thin controllers.** Business logic lives in **service classes**; validation lives
  in **FormRequests**.
- **Small, reusable React components.** Separate presentation from logic via **hooks**.
  Never duplicate UI.
- **NO hardcoded user-facing text.** Every string is a translation key with **both** an
  `en` and an `rw` entry. (See §7.)
- **Every stock movement is recorded** (who / what / when / quantity). Important actions
  write an **audit log**. Business data is **soft-deleted**, never hard-deleted.
- **RESTful**, consistent JSON responses, correct HTTP status codes, meaningful
  **localized** error messages.
- **Security:** validate everything, hash passwords, secrets in env vars, CSRF where
  relevant, rate limiting, sanitise uploads, **never trust the client**.
- **TENANT ISOLATION IS A SECURITY REQUIREMENT.** A user must **NEVER** see or touch
  another company's data. Treat any cross-company leak as a **critical bug**.

---

## 4. Folder structure

```
stockflow/
├── backend/
│   ├── app/
│   │   ├── Http/Controllers/     # thin — delegate to services
│   │   ├── Http/Middleware/      # SetTenant, etc.
│   │   ├── Http/Requests/        # FormRequest validation
│   │   ├── Http/Rules/           # reusable rules (e.g. TinNumber)
│   │   ├── Models/
│   │   ├── Models/Concerns/      # BelongsToTenant trait
│   │   ├── Models/Scopes/        # TenantScope global scope
│   │   ├── Policies/
│   │   └── Services/             # business logic
│   ├── database/{migrations,seeders,factories}/
│   ├── lang/{en,rw}/             # localized strings & validation
│   ├── routes/api.php
│   └── tests/{Feature,Unit}/
└── frontend/
    ├── src/
    │   ├── app/                  # Next.js App Router routes
    │   ├── components/           # reusable presentational UI
    │   ├── features/             # feature-scoped modules
    │   ├── hooks/                # logic separated from presentation
    │   ├── layouts/
    │   ├── locales/{en,rw}/      # translation JSON
    │   ├── services/             # API client wrapper
    │   ├── types/
    │   └── utils/
    └── ...
```

---

## 5. Coding standards

- Prefer **simple, readable, well-structured** code over clever code.
- **Comment only WHY**, not what.
- **Backend:** PSR-12, 4-space indent, formatted by **Laravel Pint**. Controllers stay
  thin; a controller method should read as: validate (FormRequest) → call service →
  return a JSON resource/envelope.
- **Frontend:** ESLint + Prettier, 2-space indent. Components are small and reusable;
  data-fetching / state logic lives in hooks, not components.
- **API responses** use a consistent envelope (see §8).
- Names are descriptive; avoid abbreviations that aren't domain terms (TIN is fine).

---

## 6. Git & commit conventions

- Branches: **`main`** (stable) and **`develop`** (integration).
- **Conventional Commits:** `feat:`, `fix:`, `chore:`, `docs:`, `test:`, `build:`,
  `refactor:`.
- **Small, logical commits.** Never dump everything into one commit.
- Write the **test with each feature**, not after.
- Review every change for the two easiest ways to erode this foundation:
  **hardcoded strings** and **cross-company data access**.

---

## 7. Bilingual rule (English + Kinyarwanda)

- **No user-facing string is ever hardcoded.** Always a translation key.
- Every key MUST exist in **both** `en` and `rw`.
  - Backend: `backend/lang/en/*.php` and `backend/lang/rw/*.php`.
  - Frontend: `frontend/src/locales/en/*.json` and `frontend/src/locales/rw/*.json`.
- Error messages returned by the API are **localized** using the request's
  `Accept-Language` header (falling back to the user's stored `locale`, then `en`).
- Where an accurate Kinyarwanda translation is not yet known, the `rw` value may
  temporarily mirror the English text — but **the key must still be present**. A full
  Kinyarwanda pass is a dedicated later session.

---

## 8. API conventions

- **Base path:** `/api`. Auth via **Sanctum bearer tokens** (see §10 decision).
- **Success envelope:**
  ```json
  { "success": true, "data": { }, "message": "optional localized string" }
  ```
- **Error envelope:**
  ```json
  { "success": false, "message": "localized string", "errors": { "field": ["..."] } }
  ```
- Correct HTTP status codes (200/201/204, 401, 403, 404, 422, 429, 500).
- **Auth failures reveal nothing** about which field was wrong (TIN vs email vs
  password) — a single generic localized "invalid credentials" message.

---

## 9. Multi-tenancy implementation

- Every tenant-owned table has a non-null **`company_id`** foreign key.
- Models use the **`BelongsToTenant`** trait, which:
  - applies the **`TenantScope`** global scope (filters by the current company), and
  - auto-fills `company_id` on create.
- **`SetTenant`** middleware resolves the current company from the authenticated user
  and binds it for the scope to read.
- The `companies` table itself is **not** tenant-scoped (it is the tenant root).
- **A tenant-isolation test is mandatory** and must always pass.

---

## 10. Recorded decisions & defaults

Decisions made during scaffolding (change only by updating this file):

| Decision | Choice | Rationale |
| --- | --- | --- |
| **Database engine** | **PostgreSQL 16 via Docker** (`docker-compose.yml`) | Brief mandates Postgres; local machine only had XAMPP/MySQL, so Docker provides Postgres without a system install. |
| **Sanctum mode** | **API tokens (Bearer)**, not SPA cookie mode | Brief says login "returns a scoped token"; simpler for a separate Next.js client. |
| **Primary keys** | **Auto-increment `bigint`** | Simpler for the foundation. Revisit before it becomes hard to reverse if UUIDs are needed. |
| **Language (frontend)** | **JavaScript** (not TypeScript) | Per brief. |
| **Package names** | `stockflow/backend` (composer), `stockflow-frontend` (npm) | — |
| **Rwanda TIN** | **9 digits** | Per brief; enforced by the reusable `TinNumber` rule. |
| **RW translations** | English placeholders allowed where the Kinyarwanda term is unknown | Keys must still exist in both locales (§7). Full RW pass is a later session. |

---

## 11. What is intentionally NOT built yet

The foundation session builds **infrastructure only**. Do NOT add product features until
requested. Deferred to later sessions (in order): shared UI kit → users & companies
management → catalog (products/categories/suppliers) → stock core & StockMovementService
→ transfer flow (request→approve→issue→confirm) → dashboards & reports → full Kinyarwanda
pass → hardening & QA.
