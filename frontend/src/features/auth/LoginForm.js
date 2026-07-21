'use client';

import { useTranslations } from 'next-intl';
import { useRouter } from 'next/navigation';
import { useState } from 'react';

import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { useAuth } from '@/hooks/useAuth';
import { ApiError } from '@/services/apiClient';
import { isValidTin } from '@/utils/tin';

// TIN + email + password login. Business logic (the API call, token handling)
// lives in the auth context/service; this component only handles presentation.
export function LoginForm() {
  const t = useTranslations('login');
  const { login } = useAuth();
  const router = useRouter();

  const [form, setForm] = useState({ tin_number: '', email: '', password: '' });
  const [fieldErrors, setFieldErrors] = useState({});
  const [formError, setFormError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  function update(field) {
    return (event) => setForm((prev) => ({ ...prev, [field]: event.target.value }));
  }

  async function onSubmit(event) {
    event.preventDefault();
    setFormError('');
    setFieldErrors({});

    if (!isValidTin(form.tin_number)) {
      setFieldErrors({ tin_number: t('error_generic') });
      return;
    }

    setSubmitting(true);
    try {
      await login(form);
      router.replace('/');
    } catch (error) {
      if (error instanceof ApiError && error.status === 422) {
        // Localized field errors from the backend validator.
        const mapped = {};
        for (const [key, messages] of Object.entries(error.errors)) {
          mapped[key] = messages[0];
        }
        setFieldErrors(mapped);
      } else {
        // Generic on purpose — never reveal which field was wrong.
        setFormError(t('error_generic'));
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={onSubmit} noValidate className="flex flex-col gap-4">
      <Field
        label={t('tin_label')}
        value={form.tin_number}
        onChange={update('tin_number')}
        placeholder={t('tin_placeholder')}
        inputMode="numeric"
        autoComplete="off"
        error={fieldErrors.tin_number}
      />
      <Field
        label={t('email_label')}
        type="email"
        value={form.email}
        onChange={update('email')}
        placeholder={t('email_placeholder')}
        autoComplete="email"
        error={fieldErrors.email}
      />
      <Field
        label={t('password_label')}
        type="password"
        value={form.password}
        onChange={update('password')}
        placeholder={t('password_placeholder')}
        autoComplete="current-password"
        error={fieldErrors.password}
      />

      {formError ? (
        <p
          role="alert"
          className="bg-danger/10 text-danger rounded-[var(--radius-control)] px-3 py-2 text-sm"
        >
          {formError}
        </p>
      ) : null}

      <Button type="submit" disabled={submitting} className="mt-2">
        {submitting ? t('submitting') : t('submit')}
      </Button>
    </form>
  );
}
