import { DEFAULT_LOCALE, isSupportedLocale, LOCALE_COOKIE } from '@/i18n/config';

// Reads the active locale from the cookie on the client. Used by the API client
// to set the Accept-Language header so the backend localizes its responses.
export function readClientLocale() {
  if (typeof document === 'undefined') {
    return DEFAULT_LOCALE;
  }

  const match = document.cookie.split('; ').find((row) => row.startsWith(`${LOCALE_COOKIE}=`));

  const value = match?.split('=')[1];

  return isSupportedLocale(value) ? value : DEFAULT_LOCALE;
}
