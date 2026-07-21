'use client';

import { useLocale, useTranslations } from 'next-intl';
import { useRouter } from 'next/navigation';
import { useTransition } from 'react';

import { LOCALES } from '@/i18n/config';
import { setUserLocale } from '@/i18n/locale';

// Switches the active locale (en <-> rw). Persists to a cookie via a server
// action, then refreshes so server components re-render with new messages.
export function LanguageSwitcher() {
  const t = useTranslations('common');
  const locale = useLocale();
  const router = useRouter();
  const [isPending, startTransition] = useTransition();

  const labels = {
    en: t('english'),
    rw: t('kinyarwanda'),
  };

  function onChange(event) {
    const next = event.target.value;
    startTransition(async () => {
      await setUserLocale(next);
      router.refresh();
    });
  }

  return (
    <label className="text-muted flex items-center gap-2 text-sm">
      <span className="sr-only">{t('language')}</span>
      <select
        value={locale}
        onChange={onChange}
        disabled={isPending}
        aria-label={t('language')}
        className="border-border bg-surface text-fg focus:border-accent h-9 rounded-[var(--radius-control)] border px-2 outline-none"
      >
        {LOCALES.map((code) => (
          <option key={code} value={code}>
            {labels[code]}
          </option>
        ))}
      </select>
    </label>
  );
}
