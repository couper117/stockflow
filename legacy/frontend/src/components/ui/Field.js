'use client';

import { useId } from 'react';

// Label + input + optional error, wired for accessibility. Reused by every form.
export function Field({ label, error, type = 'text', className = '', ...props }) {
  const id = useId();

  return (
    <div className={`flex flex-col gap-1.5 ${className}`}>
      <label htmlFor={id} className="text-fg text-sm font-medium">
        {label}
      </label>
      <input
        id={id}
        type={type}
        aria-invalid={Boolean(error)}
        className="border-border bg-surface text-fg placeholder:text-muted focus:border-accent focus:ring-accent/30 aria-[invalid=true]:border-danger h-11 rounded-[var(--radius-control)] border px-3 text-sm transition-colors outline-none focus:ring-2"
        {...props}
      />
      {error ? <p className="text-danger text-sm">{error}</p> : null}
    </div>
  );
}
