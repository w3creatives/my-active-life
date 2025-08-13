import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { CalendarDays, ChartSpline, Handshake, Settings, Trophy, Users } from 'lucide-react';
import { EventSwitcher } from './event-switcher';

const mainNavItems: NavItem[] = [
    {
        title: 'Home',
        href: '/dashboard',
        icon: CalendarDays,
    },
    {
        title: 'Stats',
        href: '/stats',
        icon: ChartSpline,
    },
    {
        title: 'Trophy Case',
        href: '/',
        icon: Trophy,
    },
    {
        title: 'Teams',
        href: '/teams',
        icon: Users,
    },
    {
        title: 'Follow',
        href: '/follow',
        icon: Users,
    },
    {
        title: 'Tutorials',
        href: '/tutorials',
        icon: Handshake,
    }
];

const footerNavItems: NavItem[] = [
  {
    title: 'Admin Dashboard',
    href: route('admin.users'),
    icon: Settings,
  },
];
const footerImpersonateNavItems: NavItem[] = [
  {
    title: 'Logout User',
    href: route('impersonate.leave'),
    icon: Settings,
  },
];

export function AppSidebar() {
  const { auth } = usePage().props as any;

  return (
    <Sidebar collapsible="icon" variant="inset">
      <SidebarHeader>
        <EventSwitcher />
      </SidebarHeader>

      <SidebarContent>
        <NavMain items={mainNavItems} className="[&_[data-active=true]]:bg-primary [&_[data-active=true]]:text-white" />
      </SidebarContent>

      {auth.is_admin && (
        <SidebarFooter>
          <NavFooter items={footerNavItems} className="mt-auto" />
        </SidebarFooter>
      )}
      {auth.is_impersonating && (
        <SidebarFooter>
          <NavFooter items={footerImpersonateNavItems} className="mt-auto" />
        </SidebarFooter>
      )}
    </Sidebar>
  );
}
