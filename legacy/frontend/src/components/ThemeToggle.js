'use client';

import { useTranslations } from 'next-intl';
import { useSyncExternalStore } from 'react';

// Toggles class-based dark mode and remembers the choice. The initial class is
// set by a blocking script in the layout (see ThemeScript) to avoid a flash.
const STORAGE_KEY = 'stockflow_theme';
const THEME_EVENT = 'stockflow:theme';

// The <html> `dark` class is the single source of truth (set pre-paint by
// ThemeScript). We read it through useSyncExternalStore so React stays in sync
// with this external DOM state — no setState-in-effect, no hydration mismatch.
function subscribe(onChange) {
  window.addEventListener(THEME_EVENT, onChange);
  window.addEventListener('storage', onChange);
  return () => {
    window.removeEventListener(THEME_EVENT, onChange);
    window.removeEventListener('storage', onChange);
  };
}

function readTheme() {
  return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
}

// Server render assumes the ThemeScript default (dark) since the DOM class is
// not yet known; the client corrects it on hydration.
function readServerTheme() {
  return 'dark';
}

export function ThemeToggle() {
  const t = useTranslations('common');
  const theme = useSyncExternalStore(subscribe, readTheme, readServerTheme);

  function toggle() {
    const next = theme === 'dark' ? 'light' : 'dark';
    document.documentElement.classList.toggle('dark', next === 'dark');
    window.localStorage.setItem(STORAGE_KEY, next);
    window.dispatchEvent(new Event(THEME_EVENT));
  }

  return (
    <button
      type="button"
      onClick={toggle}
      className="border-border bg-surface text-fg hover:bg-surface-2 flex h-9 items-center gap-2 rounded-[var(--radius-control)] border px-3 text-sm transition-colors"
      aria-label={t('theme')}
    >
      {theme === 'dark' ? t('theme_light') : t('theme_dark')}
    </button>
  );
}

// Blocking script: applies the saved theme (default dark) before first paint.
export function ThemeScript() {
  const code = `(function(){try{var t=localStorage.getItem('${STORAGE_KEY}');if(!t){t='dark';}document.documentElement.classList.toggle('dark',t==='dark');}catch(e){document.documentElement.classList.add('dark');}})();`;

  return <script dangerouslySetInnerHTML={{ __html: code }} />;
}
