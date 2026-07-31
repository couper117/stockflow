'use client';

import { useTranslations } from 'next-intl';

import { ProtectedRoute } from '@/components/ProtectedRoute';
import { AppHeader } from '@/layouts/AppHeader';
import { useAuth } from '@/hooks/useAuth';

// Protected home. Intentionally feature-free — this is the foundation build.
function HomeContent() {
  const t = useTranslations();
  const { user, company, role } = useAuth();

  // The backend sends a translation key (e.g. "roles.super_admin"); translate it.
  const roleLabel = role?.label_key ? t(role.label_key) : (role?.name ?? '');

  return (
    <main className="mx-auto w-full max-w-3xl flex-1 px-4 py-8 sm:px-6">
      <h1 className="text-fg text-xl font-semibold">{t('home.title')}</h1>
      <p className="text-muted mt-1">{t('home.welcome', { name: user?.name ?? '' })}</p>

      <div className="mt-6 grid gap-4 sm:grid-cols-2">
        <div className="border-border bg-surface rounded-[var(--radius-card)] border p-4">
          <p className="text-muted text-sm">{t('home.company')}</p>
          <p className="text-fg mt-1 font-medium">{company?.name}</p>
          <p className="text-muted text-sm">TIN {company?.tin_number}</p>
        </div>
        <div className="border-border bg-surface rounded-[var(--radius-card)] border p-4">
          <p className="text-muted text-sm">{t('home.role')}</p>
          <p className="text-fg mt-1 font-medium">{roleLabel}</p>
        </div>
      </div>

      <p className="border-border text-muted mt-8 rounded-[var(--radius-card)] border border-dashed p-4 text-sm">
        {t('home.foundation_note')}
      </p>
    </main>
  );
}

export default function Home() {
  return (
    <ProtectedRoute>
      <div className="bg-bg flex min-h-dvh flex-col">
        <AppHeader />
        <HomeContent />
      </div>
    </ProtectedRoute>
  );
}
