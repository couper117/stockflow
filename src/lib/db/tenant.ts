import { sql } from 'drizzle-orm';

import { db, type Tx } from './index';

// ★ The isolation entry point. Every authenticated request runs its data access
// inside withBusinessContext, which opens a transaction and sets app.business_id
// with SET LOCAL — scoped to THIS transaction, so a pooled connection can never
// leak context between requests. Row-level security policies compare business_id
// to current_setting('app.business_id'); with no context set it is NULL and the
// policies match nothing (a request with no context reads ZERO rows, never all).
//
// Never set the business id from the request body — only from the session.
export async function withBusinessContext<T>(
  businessId: string,
  fn: (tx: Tx) => Promise<T>,
): Promise<T> {
  return db.transaction(async (tx) => {
    // set_config(name, value, is_local=true) === SET LOCAL, but parameterised.
    await tx.execute(sql`select set_config('app.business_id', ${businessId}, true)`);
    return fn(tx);
  });
}

// Escape hatch for the few legitimately cross-tenant reads (resolving a business
// by TIN at login, before any tenant is set). Uses no context, so it can only
// touch tables that are NOT tenant-scoped (e.g. businesses). Use sparingly.
export async function withoutBusinessContext<T>(fn: (tx: Tx) => Promise<T>): Promise<T> {
  return db.transaction(async (tx) => fn(tx));
}
