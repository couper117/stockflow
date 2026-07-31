import { getRequestConfig } from 'next-intl/server';

import en from './messages/en.json';
import rw from './messages/rw.json';
import { getUserLocale } from './locale';

const messages = { en, rw } as const;

// next-intl reads this on every request to pick the active locale + messages.
export default getRequestConfig(async () => {
  const locale = await getUserLocale();
  return { locale, messages: messages[locale] };
});
