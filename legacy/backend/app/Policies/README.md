# Policies

Laravel authorization policies live here (`App\Policies`).

Nothing to authorize yet in the foundation — auth is currently just
login/logout/me. As feature models arrive (users, shops, products, stock
movements), add a policy per model and register it (Laravel auto-discovers
`App\Policies\{Model}Policy`).

Policies enforce **role permissions** (who may do what). They sit alongside — not
instead of — the two data-visibility guarantees enforced globally by scopes:

- **Tenant isolation** (`TenantScope`, see `CLAUDE.md` §9)
- **Shop-level privacy** (`ShopScope`, see `CLAUDE.md` §9.1)

Never rely on a policy alone to hide another company's or another shop's data;
that is the scopes' job.
