import {
  DashboardIcon,
  InventoryIcon,
  ReportsIcon,
  SettingsIcon,
  ShopsIcon,
  SuppliersIcon,
  SupportIcon,
  TakeOutIcon,
} from '@/components/icons';

// Navigation is role-scoped: each item lists the roles allowed to see it. This
// mirrors the backend's visibility rules in the UI — a role never sees links to
// areas it can't use. `ready: false` items are shown but not yet navigable
// (their screens arrive in later sessions); only `/` (Dashboard) exists today.
//
// Roles: super_admin | stock_manager | shopkeeper | boss
const ALL_ROLES = ['super_admin', 'stock_manager', 'shopkeeper', 'boss'];

export const primaryNav = [
  {
    key: 'nav.dashboard',
    href: '/',
    Icon: DashboardIcon,
    roles: ALL_ROLES,
    ready: true,
  },
  {
    key: 'nav.inventory',
    href: '/inventory',
    Icon: InventoryIcon,
    roles: ALL_ROLES,
    ready: false,
  },
  {
    key: 'nav.takeouts',
    href: '/take-outs',
    Icon: TakeOutIcon,
    roles: ['super_admin', 'stock_manager', 'shopkeeper'],
    ready: false,
  },
  {
    key: 'nav.shops',
    href: '/shops',
    Icon: ShopsIcon,
    roles: ['super_admin'],
    ready: false,
  },
  {
    key: 'nav.suppliers',
    href: '/suppliers',
    Icon: SuppliersIcon,
    roles: ['super_admin'],
    ready: false,
  },
  {
    key: 'nav.reports',
    href: '/reports',
    Icon: ReportsIcon,
    roles: ['super_admin', 'boss'],
    ready: false,
  },
];

export const secondaryNav = [
  {
    key: 'nav.settings',
    href: '/settings',
    Icon: SettingsIcon,
    roles: ['super_admin'],
    ready: false,
  },
  {
    key: 'nav.support',
    href: '/support',
    Icon: SupportIcon,
    roles: ALL_ROLES,
    ready: false,
  },
];

// Items a role is allowed to see, from a given list.
export function navForRole(items, roleName) {
  if (!roleName) {
    return [];
  }

  return items.filter((item) => item.roles.includes(roleName));
}
