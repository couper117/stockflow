import { defineConfig } from 'drizzle-kit';

// drizzle-kit runs outside Next, so it reads process.env directly (allowed by the
// ESLint override for this file). Migrations use the sf_migrate role
// (DATABASE_URL_MIGRATE), which owns the schema; the runtime app never does DDL.
const url = process.env.DATABASE_URL_MIGRATE ?? process.env.DATABASE_URL;
if (!url) {
  throw new Error('DATABASE_URL_MIGRATE (or DATABASE_URL) must be set for migrations.');
}

export default defineConfig({
  dialect: 'postgresql',
  schema: './src/lib/db/schema/index.ts',
  out: './drizzle',
  dbCredentials: { url },
  strict: true,
  verbose: true,
});
