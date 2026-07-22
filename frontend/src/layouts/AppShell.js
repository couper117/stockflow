import { AppHeader } from '@/layouts/AppHeader';
import { BottomNav } from '@/layouts/BottomNav';
import { SidebarNav } from '@/layouts/SidebarNav';

// The authenticated app frame every feature screen renders inside:
// desktop sidebar + top header + mobile bottom-nav. Mobile-first — the sidebar
// appears from md up, the bottom-nav below it.
export function AppShell({ children }) {
  return (
    <div className="bg-bg flex min-h-dvh">
      <SidebarNav />
      <div className="flex min-h-dvh min-w-0 flex-1 flex-col">
        <AppHeader />
        <main className="flex-1">{children}</main>
        <BottomNav />
      </div>
    </div>
  );
}
