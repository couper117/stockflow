# StockFlow — Backend (Laravel API)

Laravel 12 REST API for StockFlow. PHP 8.2+, PostgreSQL, Sanctum token auth.
See the repo-root [`CLAUDE.md`](../CLAUDE.md) for architecture rules and conventions.

## Requirements

- PHP 8.2+ with the **`pdo_pgsql`** and **`pgsql`** extensions enabled
- Composer
- PostgreSQL 16 (the repo ships a `docker-compose.yml` at the root)

> **XAMPP note:** the pgsql drivers ship with XAMPP but are disabled by default.
> Enable them in `php.ini` (uncomment `extension=pdo_pgsql` and `extension=pgsql`)
> and restart PHP.

## Setup

```bash
# from the repo root, start Postgres first:
docker compose up -d

cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve            # http://localhost:8000
```

### Demo login (from the seeders)

| Field | Value |
| --- | --- |
| TIN | `100200300` |
| Email | `admin@demo.test` |
| Password | `password` |

## Scripts

| Task | Command |
| --- | --- |
| Dev server | `php artisan serve` |
| Test | `php artisan test` |
| Lint / format | `./vendor/bin/pint` |
| Lint (check only) | `./vendor/bin/pint --test` |
| Fresh DB + seed | `php artisan migrate:fresh --seed` |

Tests run against an **in-memory SQLite** database (configured in `phpunit.xml`),
so they need no running Postgres.

## API surface (foundation)

| Method | Path | Auth | Purpose |
| --- | --- | --- | --- |
| POST | `/api/auth/login` | public | TIN + email + password → returns a scoped bearer token |
| POST | `/api/auth/logout` | bearer | Revoke the current token |
| GET | `/api/auth/me` | bearer | The authenticated user + company + role |

All responses use the envelope described in `CLAUDE.md §8`. Errors are localized via
the `Accept-Language` header (`en` / `rw`).

## Key building blocks

- `app/Support/Tenancy.php` — per-request current-company holder (singleton).
- `app/Models/Scopes/TenantScope.php` + `app/Models/Concerns/BelongsToTenant.php` —
  automatic tenant isolation for every tenant-owned model.
- `app/Http/Middleware/SetTenant.php` — binds the caller's company after auth.
- `app/Http/Middleware/SetLocale.php` — negotiates response language.
- `app/Rules/TinNumber.php` — reusable Rwanda TIN (9-digit) rule.
- `app/Services/AuthService.php` — authentication business logic (thin controller).
