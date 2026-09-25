'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useTranslations } from 'next-intl';

import { useAuth } from '@/hooks/useAuth';
import { navForRole, primaryNav, secondaryNav } from '@/layouts/navItems';

function isActive(pathname, href) {
  return href === '/' ? pathname === '/' : pathname.startsWith(href);
}

// One sidebar row. A ready item is a link; a not-yet-built item is shown but
// non-interactive with a "soon" badge, so the shell reads as complete without
// leading anywhere broken.
function SidebarLink({ item, active, t }) {
  const { Icon } = item;
  const label = t(item.key);

  const inner = (
    <>
      <Icon className="shrink-0" />
      <span className="truncate">{label}</span>
    </>
  );

  if (!item.ready) {
    return (
      <span
        aria-disabled="true"
        className="text-muted flex items-center gap-3 rounded-[var(--radius-control)] px-3 py-2 text-sm opacity-60"
      >
        {inner}
        <span className="border-border text-muted ml-auto rounded-full border px-2 py-0.5 text-[10px] tracking-wide uppercase">
          {t('nav.soon')}
        </span>
      </span>
    );
  }

  return (
    <Link
      href={item.href}
      aria-current={active ? 'page' : undefined}
      className={`flex items-center gap-3 rounded-[var(--radius-control)] px-3 py-2 text-sm transition-colors ${
        active
          ? 'bg-primary/10 text-primary dark:bg-accent/15 dark:text-accent font-medium'
          : 'text-fg hover:bg-surface-2'
      }`}
    >
      {inner}
    </Link>
  );
}

// Desktop navigation rail. Hidden on mobile (the bottom nav takes over there).
export function SidebarNav() {
  const t = useTranslations();
  const pathname = usePathname();
  const { role } = useAuth();
  const roleName = role?.name ?? null;

  const primary = navForRole(primaryNav, roleName);
  const secondary = navForRole(secondaryNav, roleName);
  const roleLabel = role?.label_key ? t(role.label_key) : (role?.name ?? '');

  return (
    <aside className="border-border bg-surface hidden w-60 shrink-0 flex-col border-r md:flex">
      <div className="border-border flex items-center gap-2 border-b px-5 py-4">
        <span className="bg-primary dark:bg-accent flex h-8 w-8 items-center justify-center rounded-[var(--radius-control)] text-sm font-bold text-white">
          {t('app.name').charAt(0)}
        </span>
        <span className="flex flex-col leading-tight">
          <span className="text-fg text-sm font-semibold">{t('app.name')}</span>
          {roleLabel ? <span className="text-muted text-xs">{roleLabel}</span> : null}
        </span>
      </div>

      <nav aria-label={t('nav.primary')} className="flex flex-1 flex-col gap-1 px-3 py-4">
        {primary.map((item) => (
          <SidebarLink key={item.key} item={item} active={isActive(pathname, item.href)} t={t} />
        ))}

        {secondary.length > 0 ? (
          <div className="border-border mt-auto flex flex-col gap-1 border-t pt-4">
            {secondary.map((item) => (
              <SidebarLink
                key={item.key}
                item={item}
                active={isActive(pathname, item.href)}
                t={t}
              />
            ))}
          </div>
        ) : null}
      </nav>
    </aside>
  );
}
