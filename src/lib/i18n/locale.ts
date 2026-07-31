'use server';

// Server actions that read/write the user's chosen locale in a cookie. A cookie
// (not a URL prefix) keeps authenticated routes clean. When a user is signed in,
// their stored `locale` takes precedence; this cookie covers the public pages.
import { cookies } from 'next/headers';

import { DEFAULT_LOCALE, isSupportedLocale, LOCALE_COOKIE, type Locale } from './config';

export async function getUserLocale(): Promise<Locale> {
  const store = await cookies();
  const value = store.get(LOCALE_COOKIE)?.value;
  return isSupportedLocale(value) ? value : DEFAULT_LOCALE;
}

export async function setUserLocale(locale: Locale): Promise<void> {
  if (!isSupportedLocale(locale)) return;
  const store = await cookies();
  store.set(LOCALE_COOKIE, locale, {
    path: '/',
    maxAge: 60 * 60 * 24 * 365,
    sameSite: 'lax',
  });
}
