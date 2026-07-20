# StockFlow

**Multi-company (multi-tenant) Stock Management System for local & small businesses in Rwanda.**
Mobile-first, dark-mode first-class, fully bilingual (English + Kinyarwanda).

Goods flow: **Suppliers → Main Stock → Shops.** Companies sign in with their
**TIN + email + password**. Moving stock to a shop is a simple internal flow:
`request → approve → issue → confirm`. This is **not** a logistics app (no maps,
drivers, routes, or GPS).

> **Read [`CLAUDE.md`](./CLAUDE.md) first.** It is the authoritative source for the
> stack, architecture rules, folder structure, coding standards, and conventions.

---

## Monorepo layout

```
stockflow/
├── backend/            # Laravel REST API (PHP 8.2+, PostgreSQL, Sanctum)
├── frontend/           # Next.js App Router (JavaScript, Tailwind, next-intl)
├── docker-compose.yml  # Local PostgreSQL 16
├── CLAUDE.md           # Project standards (authoritative)
└── README.md
```

## Roles

| Role | Capability |
| --- | --- |
| Super Administrator | Manages the company, staff, shops, and stock |
| Stock Manager | Runs the Main Stock |
| Shopkeeper | Runs a single shop |
| Boss | Read-only overview |

---

## Prerequisites

- **Node 20+** and npm
- **PHP 8.2+** and **Composer**
- **Docker** (for the local PostgreSQL 16 service)

## Quick start

```bash
# 1. Start the database
cp .env.example .env
docker compose up -d

# 2. Backend  (see backend/README.md)
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve            # http://localhost:8000

# 3. Frontend (see frontend/README.md, in a second terminal)
cd frontend
cp .env.local.example .env.local
npm install
npm run dev                  # http://localhost:3000
```

Demo login is created by the seeders — see `backend/README.md`.

## Common scripts

| Task | Backend | Frontend |
| --- | --- | --- |
| Dev server | `php artisan serve` | `npm run dev` |
| Build | — | `npm run build` |
| Test | `php artisan test` | `npm run test` |
| Lint | `./vendor/bin/pint` | `npm run lint` |

## Branching

- `main` — stable, release-ready
- `develop` — integration branch for ongoing work

Commits follow [Conventional Commits](https://www.conventionalcommits.org/)
(`feat:`, `fix:`, `chore:`, `docs:`, `test:`, `build:`).

---

_StockFlow · foundation scaffold._
