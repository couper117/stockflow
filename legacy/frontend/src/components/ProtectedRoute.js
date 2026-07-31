'use client';

import { useTranslations } from 'next-intl';
import { useRouter } from 'next/navigation';
import { useEffect } from 'react';

import { useAuth } from '@/hooks/useAuth';

// Client-side guard for authenticated pages. Redirects guests to /login and
// shows a loading state while the session is being restored.
export function ProtectedRoute({ children }) {
  const { status } = useAuth();
  const router = useRouter();
  const t = useTranslations('common');

  useEffect(() => {
    if (status === 'guest') {
      router.replace('/login');
    }
  }, [status, router]);

  if (status !== 'authenticated') {
    return <div className="text-muted flex flex-1 items-center justify-center">{t('loading')}</div>;
  }

  return children;
}
