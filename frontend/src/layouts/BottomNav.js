'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useTranslations } from 'next-intl';

import { useAuth } from '@/hooks/useAuth';
import { navForRole, primaryNav } from '@/layouts/navItems';

function isActive(pathname, href) {
  return href === '/' ? pathname === '/' : pathname.startsWith(href);
}

function BottomLink({ item, active, t }) {
  const { Icon } = item;
  const label = t(item.key);

  const inner = (
    <>
      <Icon />
      <span className="text-[11px]">{label}</span>
    </>
  );

  const shared = 'flex flex-1 flex-col items-center justify-center gap-1 py-2';

  if (!item.ready) {
    return (
      <span aria-disabled="true" className={`text-muted opacity-60 ${shared}`}>
        {inner}
      </span>
    );
  }

  return (
    <Link
      href={item.href}
      aria-current={active ? 'page' : undefined}
      className={`${shared} ${active ? 'text-primary dark:text-accent' : 'text-muted'}`}
    >
      {inner}
    </Link>
  );
}

// Mobile navigation bar (phone-first). Hidden on desktop, where the sidebar
// takes over. Shows the first few items the role is allowed to see.
export function BottomNav() {
  const t = useTranslations();
  const pathname = usePathname();
  const { role } = useAuth();

  const items = navForRole(primaryNav, role?.name ?? null).slice(0, 4);

  return (
    <nav
      aria-label={t('nav.primary')}
      className="border-border bg-surface sticky bottom-0 z-10 flex items-stretch border-t md:hidden"
    >
      {items.map((item) => (
        <BottomLink key={item.key} item={item} active={isActive(pathname, item.href)} t={t} />
      ))}
    </nav>
  );
}
