import { getTranslations } from 'next-intl/server';

// Placeholder landing page — proves the app boots with i18n wired. The real
// (auth) and (app) route groups arrive with SF-005 (auth) and the feature tickets.
export default async function HomePage() {
  const t = await getTranslations('app');

  return (
    <main className="mx-auto flex min-h-dvh max-w-md flex-col justify-center gap-3 px-6">
      <h1 className="text-2xl font-semibold">{t('name')}</h1>
      <p className="text-neutral-500 dark:text-neutral-400">{t('tagline')}</p>
      <p className="text-sm text-neutral-400">
        Foundation scaffold. See <code>../architecture/</code> and{' '}
        <code>CLAUDE.md</code>.
      </p>
    </main>
  );
}
