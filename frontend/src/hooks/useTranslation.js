'use client';

import { useTranslations } from 'next-intl';

// Project-wide translation helper. Wraps next-intl's `useTranslations` so
// components import from one place (`@/hooks/useTranslation`) and we keep a
// single seam to extend later (e.g. default namespaces, fallbacks) without
// touching every call site.
//
//   const t = useTranslation('login');
//   t('title');
export function useTranslation(namespace) {
  return useTranslations(namespace);
}
