import { sql } from 'drizzle-orm';

import { db } from '@/lib/db';

// FR-SYS-05: reports app + database status without auth and without leaking
// version/config. 200 when healthy, 503 when the database is unreachable.
export async function GET() {
  try {
    await db.execute(sql`select 1`);
    return Response.json({ status: 'ok', db: 'ok' }, { status: 200 });
  } catch {
    return Response.json({ status: 'degraded', db: 'unreachable' }, { status: 503 });
  }
}
