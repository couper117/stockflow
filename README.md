# StockFlow

**Multi-business (multi-tenant) stock management, sold as a subscription, for small retailers.**
Goods flow **supplier → stock room(s) → shop(s)**. Businesses sign in with **TIN + email +
password**. The core flow is the seller→stock-manager transfer: `request → accept → fulfil`.
Mobile-first, dark-mode first-class, bilingual EN + RW. **Not** a POS, warehouse, or accounting
system.

> **Design lives in [`../architecture/`](../architecture/README.md)** — the merged SF-DOC/NT spec.
> **[`CLAUDE.md`](./CLAUDE.md)** holds the authoritative stack + rules.

One **Next.js full-stack codebase** (TypeScript) · PostgreSQL 16 with **row-level security** ·
Drizzle ORM · Zod · next-intl. The prior Laravel + separate-frontend build is kept under
`legacy/` for reference only.

## Prerequisites
- Node 20+ and npm
- Docker (local PostgreSQL 16)

## Quick start
```bash
cp .env.example .env
docker compose up -d          # Postgres 16
npm install
npm run db:migrate            # apply migrations (once they exist)
npm run seed                  # two businesses, so isolation is visible from day one
npm run dev                   # http://localhost:3000
```
Or `npm run setup` to do install → compose up → wait healthy → migrate → seed in one step.

## Scripts
| Task | Command |
|---|---|
| Dev | `npm run dev` |
| Build | `npm run build` |
| Verify (lint+types+test) | `npm run verify` |
| Unit/integration tests | `npm run test` |
| E2E | `npm run test:e2e` |
| Generate migration | `npm run db:generate` |
| Migrate / reset | `npm run db:migrate` / `npm run db:reset` |
| Seed | `npm run seed` |

## Isolation check (day one)
`npm run seed` creates **two** businesses. Sign in as a seller of business A and try to open a
product of business B — you get a **404**. That is the tenant-isolation guarantee, enforced by
Postgres RLS. Worth seeing first.

## Branching
`main` (stable) · `develop` (integration). Conventional Commits.
