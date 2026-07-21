'use server';

// Server actions that read/write the user's chosen locale in a cookie.
// We use a cookie (not a URL prefix) so authenticated routes stay clean.
import { cookies } from 'next/headers';

import { DEFAULT_LOCALE, isSupportedLocale, LOCALE_COOKIE } from './config';

export async function getUserLocale() {
  const store = await cookies();
  const value = store.get(LOCALE_COOKIE)?.value;

  return isSupportedLocale(value) ? value : DEFAULT_LOCALE;
}

export async function setUserLocale(locale) {
  if (!isSupportedLocale(locale)) {
    return;
  }

  const store = await cookies();
  store.set(LOCALE_COOKIE, locale, {
    path: '/',
    maxAge: 60 * 60 * 24 * 365, // 1 year
    sameSite: 'lax',
  });
}
