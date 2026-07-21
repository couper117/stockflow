'use client';

import { useTranslations } from 'next-intl';
import { useRouter } from 'next/navigation';
import { useEffect } from 'react';

import { LanguageSwitcher } from '@/components/LanguageSwitcher';
import { ThemeToggle } from '@/components/ThemeToggle';
import { LoginForm } from '@/features/auth/LoginForm';
import { useAuth } from '@/hooks/useAuth';

export default function LoginPage() {
  const t = useTranslations();
  const { status } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (status === 'authenticated') {
      router.replace('/');
    }
  }, [status, router]);

  return (
    <div className="bg-bg flex min-h-dvh flex-col">
      <header className="flex items-center justify-end gap-2 px-4 py-3 sm:px-6">
        <LanguageSwitcher />
        <ThemeToggle />
      </header>

      <main className="flex flex-1 items-center justify-center px-4 py-10">
        <div className="border-border bg-surface w-full max-w-sm rounded-[var(--radius-card)] border p-6 shadow-sm sm:p-8">
          <div className="mb-6 text-center">
            <p className="text-primary dark:text-accent text-2xl font-bold">{t('app.name')}</p>
            <h1 className="text-fg mt-4 text-lg font-semibold">{t('login.title')}</h1>
            <p className="text-muted mt-1 text-sm">{t('login.subtitle')}</p>
          </div>
          <LoginForm />
        </div>
      </main>
    </div>
  );
}
