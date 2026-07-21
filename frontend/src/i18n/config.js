// Shared i18n constants — safe to import from both server and client code.
export const LOCALES = ['en', 'rw'];
export const DEFAULT_LOCALE = 'en';
export const LOCALE_COOKIE = 'STOCKFLOW_LOCALE';

export function isSupportedLocale(value) {
  return LOCALES.includes(value);
}
