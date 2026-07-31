import { z } from 'zod';

// The ONE place allowed to read process.env (an ESLint rule enforces this).
// Parsed with Zod at boot; on failure we print exactly what is wrong and exit(1),
// so a misconfigured deploy fails fast and loudly instead of at the first query.

const EnvSchema = z.object({
  NODE_ENV: z.enum(['development', 'test', 'production']).default('development'),

  DATABASE_URL: z.string().url(),
  DATABASE_URL_MIGRATE: z.string().url().optional(),

  SESSION_COOKIE_NAME: z.string().min(1).default('sf_session'),
  SESSION_ABSOLUTE_HOURS: z.coerce.number().int().positive().default(12),
  SESSION_IDLE_MINUTES: z.coerce.number().int().positive().default(60),

  WEB_ORIGIN: z.string().url().default('http://localhost:3000'),
  DEFAULT_LANGUAGE: z.enum(['en', 'rw']).default('en'),
  LOG_LEVEL: z.enum(['debug', 'info', 'warn', 'error']).default('info'),

  RATE_LIMIT_LOGIN_PER_15MIN: z.coerce.number().int().positive().default(5),
  RATE_LIMIT_TIN_LOOKUP_PER_MIN: z.coerce.number().int().positive().default(10),
  RATE_LIMIT_API_PER_MIN: z.coerce.number().int().positive().default(100),

  MAIL_HOST: z.string().default('localhost'),
  MAIL_PORT: z.coerce.number().int().positive().default(1025),
  MAIL_FROM: z.string().default('no-reply@stockflow.local'),
});

export type Env = z.infer<typeof EnvSchema>;

function loadEnv(): Env {
  const parsed = EnvSchema.safeParse(process.env);
  if (!parsed.success) {
    const issues = parsed.error.issues
      .map((i) => `  - ${i.path.join('.') || '(root)'}: ${i.message}`)
      .join('\n');
    console.error(`\nInvalid environment configuration:\n${issues}\n`);
    process.exit(1);
  }
  return parsed.data;
}

// Frozen, typed config. Import this everywhere instead of process.env.
export const env: Readonly<Env> = Object.freeze(loadEnv());
