'use client';

import { useTranslations } from 'next-intl';

import { LanguageSwitcher } from '@/components/LanguageSwitcher';
import { ThemeToggle } from '@/components/ThemeToggle';
import { Button } from '@/components/ui/Button';
import { useAuth } from '@/hooks/useAuth';

// App chrome for authenticated pages: brand, language + theme controls, sign out.
export function AppHeader() {
  const t = useTranslations();
  const { logout, isAuthenticated } = useAuth();

  return (
    <header className="border-border bg-surface flex items-center justify-between border-b px-4 py-3 sm:px-6">
      <span className="text-primary dark:text-accent text-lg font-semibold">{t('app.name')}</span>
      <div className="flex items-center gap-2 sm:gap-3">
        <LanguageSwitcher />
        <ThemeToggle />
        {isAuthenticated ? (
          <Button variant="ghost" onClick={logout}>
            {t('common.sign_out')}
          </Button>
        ) : null}
      </div>
    </header>
  );
}
