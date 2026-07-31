import { drizzle } from 'drizzle-orm/postgres-js';
import postgres from 'postgres';

import { env } from '@/lib/env';

import * as schema from './schema';

// Runtime connection uses the sf_app role (DATABASE_URL) — WITHOUT BYPASSRLS.
// Tenant isolation depends on this role not being able to see across businesses.
// A single pool is reused across requests; per-request tenant context is set with
// SET LOCAL inside a transaction (see ./tenant.ts), never on the pooled connection.
const client = postgres(env.DATABASE_URL, {
  max: 10,
  prepare: false,
});

export const db = drizzle(client, { schema });
export type Db = typeof db;

// A transaction handle, for services that take an optional tx.
export type Tx = Parameters<Parameters<Db['transaction']>[0]>[0];
