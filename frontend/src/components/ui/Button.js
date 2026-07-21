'use client';

// Minimal, reusable button. The shared UI kit (variants, sizes, etc.) is built
// in a later session — this covers the foundation's needs without duplication.
const VARIANTS = {
  primary: 'bg-primary text-white hover:bg-primary-hover focus-visible:outline-primary',
  ghost: 'bg-transparent text-fg hover:bg-surface-2 focus-visible:outline-border',
};

export function Button({
  type = 'button',
  variant = 'primary',
  disabled = false,
  className = '',
  children,
  ...props
}) {
  return (
    <button
      type={type}
      disabled={disabled}
      className={`inline-flex h-11 items-center justify-center rounded-[var(--radius-control)] px-4 text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60 ${VARIANTS[variant]} ${className}`}
      {...props}
    >
      {children}
    </button>
  );
}
