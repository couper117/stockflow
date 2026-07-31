import postgres from 'postgres';

// Polls the database until it accepts a connection, so `npm run setup` can wait
// for the Docker Postgres healthcheck before migrating. Reads env directly
// (scripts are exempt from the no-process.env rule).
const url = process.env.DATABASE_URL_MIGRATE ?? process.env.DATABASE_URL;
if (!url) {
  console.error('DATABASE_URL(_MIGRATE) is not set.');
  process.exit(1);
}

const RETRIES = 30;
const DELAY_MS = 1000;

async function main() {
  for (let attempt = 1; attempt <= RETRIES; attempt++) {
    const sql = postgres(url!, { max: 1, connect_timeout: 3 });
    try {
      await sql`select 1`;
      await sql.end({ timeout: 1 });
      console.log('Database is ready.');
      return;
    } catch {
      await sql.end({ timeout: 1 }).catch(() => {});
      console.log(`Waiting for database… (${attempt}/${RETRIES})`);
      await new Promise((r) => setTimeout(r, DELAY_MS));
    }
  }
  console.error('Database did not become ready in time.');
  process.exit(1);
}

void main();
