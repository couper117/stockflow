# HANDOFF

## Current Task
Frontend foundation: Next.js + Tailwind (8px grid), design tokens
(deep-blue primary, status colors, neutral gray, radii, shadows), first-class
dark mode, and next-intl i18n (en + rw) with a `useTranslation` helper.
Deliver on a branch called `Levi` and push it.

## Status
In progress — the scaffold already existed on `main` and was ~95% complete.
Closed the three literal gaps against the task; verifying build, then
branch + push.

## Progress
- [x] Next.js app + Tailwind v4 (already scaffolded, confirmed)
- [x] Design tokens: primary/status/neutral/radii — added **neutral ramp** + **shadow tokens**
- [x] 8px spacing grid made explicit in `@theme` (`--spacing: 0.25rem`, even steps = 8px)
- [x] `useTranslation` helper hook added and wired into the home page
- [x] Dark mode toggle + en/rw sample strings (pre-existing, confirmed)
- [ ] `next build` passes (deps installing)
- [ ] Commit on branch `Levi` and push to origin

## Working Notes
Edits made this session:
- `frontend/src/app/globals.css` — added `--color-neutral-*` ramp, explicit
  `--spacing`, and `--shadow-card/-control/-overlay`.
- `frontend/src/hooks/useTranslation.js` — new thin wrapper over next-intl.
- `frontend/src/app/page.js` — now imports the `useTranslation` helper.
Next step: once `npm install` finishes, run `npx next build`; if green,
`git checkout -b Levi`, commit the frontend additions, `git push -u origin Levi`.

## Recently Completed
- Backend: company registration endpoint + seeders + tests.
- Backend: shop scoping / shop-visibility mechanism + tests.
