# StockFlow — Frontend

Next.js (App Router) + React in **JavaScript**, Tailwind CSS, and i18n via
**next-intl** (`en` + Kinyarwanda `rw`). This is the client for the StockFlow API.

See the repository root [`CLAUDE.md`](../CLAUDE.md) for the product brief and the
architecture rules this app must follow (no hardcoded strings, small reusable
components, logic in hooks).

## Requirements

- **Node 20+**
- The [backend API](../backend/README.md) running (default `http://localhost:8000/api`)

## Getting started

```bash
npm install
npm run dev            # http://localhost:3000
```

Set the API base URL if it differs from the default:

```bash
# .env.local
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

## Scripts

| Task                | Command                |
| ------------------- | ---------------------- |
| Dev server          | `npm run dev`          |
| Production build    | `npm run build`        |
| Start built app     | `npm run start`        |
| Lint                | `npm run lint`         |
| Format (write)      | `npm run format`       |
| Format (check only) | `npm run format:check` |
| Test                | `npm run test`         |
| Test (watch)        | `npm run test:watch`   |

## Layout

```
src/
├── app/          # App Router routes (/, /login)
├── components/   # reusable presentational UI (Button, Field, toggles)
├── context/      # AuthContext (auth + tenant + locale)
├── features/     # feature-scoped modules (auth/LoginForm)
├── hooks/        # logic separated from presentation (useAuth)
├── i18n/         # next-intl configuration
├── layouts/      # AppHeader, etc.
├── locales/      # en.json + rw.json translation catalogs
├── services/     # apiClient wrapper + authService
└── utils/        # helpers (tin, clientLocale)
```

## Internationalisation

Every user-facing string is a translation key present in **both** `locales/en.json`
and `locales/rw.json`. Never hardcode text. The language switcher toggles `en` / `rw`
at runtime and the API client forwards the choice via `Accept-Language`.
