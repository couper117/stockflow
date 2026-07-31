# StockFlow — Merged Architecture

This folder is the **single source of truth** for the merged StockFlow system. It reconciles
two prior efforts into one buildable design:

- **The STOCK pack** (`Desktop/STOCK/*.pdf`, SF-DOC-01…13) — a rigorous paper specification for
  a multi-tenant stock system (per-location stock, immutable movements, the seller→stock-manager
  transfer workflow, dynamic units, daily report, subscription billing). No code.
- **The NT system** (`Desktop/NT/stockflow`) — a real, running Laravel + Next.js foundation with
  working bilingual EN/RW, tenant isolation via ORM global scopes, and a shop-level privacy
  mechanism. Foundation only (companies, roles, shops, users, auth).

The merge keeps the **STOCK pack's domain rigor** and the **NT system's working frontend and
bilingual plumbing**, on a **single Next.js full-stack codebase** with **PostgreSQL row-level
security** for tenant isolation.

## Read in this order

| # | Document | What it gives you |
|---|----------|-------------------|
| 00 | [Merge decisions](./00-merge-decisions.md) | Every reconciliation, each conflict + the call, the canonical stack. **Read first.** |
| 01 | [Requirements (SRS)](./01-requirements.md) | Roles, permission matrix, functional requirements by module, phase split. |
| 02 | [System architecture](./02-system-architecture.md) | Codebase shape, layering, tenancy+RLS, the stock transaction, the request workflow, ADRs. |
| 03 | [Database design](./03-database-design.md) | Schema, constraints, RLS policies, Drizzle + migrations. |
| 04 | [API specification](./04-api-specification.md) | Endpoints, envelopes, error codes, validation. |
| 05 | [Implementation plan](./05-implementation-plan.md) | Ticketed, ordered roadmap and the day-1 bootstrap. |

## The one rule that outranks everything

**Multi-business tenant isolation, enforced by the database (row-level security), not only by
application code.** A user must never see or touch another business's data. Every tenant-owned
table carries `business_id`, has RLS enabled + forced + a policy, and the app connects as a role
without `BYPASSRLS`. A request with no tenant context reads **zero** rows, never all rows. Treat
any cross-tenant leak as a critical defect.
