import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { usePage } from '@inertiajs/react';
import {
  BookImage,
  CalendarDays,
  CalendarPlus,
  ChartSpline,
  Goal,
  Handshake,
  Home,
  Settings,
  Star,
  Trophy,
  UserPlus,
  Users,
  Wand2,
  Archive,
  Wrench,
} from 'lucide-react';
import { EventSwitcher } from './event-switcher';

// Standard nav items for regular/month events
const mainNavItems: NavItem[] = [
  {
    title: 'Home',
    href: '/dashboard',
    icon: CalendarDays,
  },
  {
    title: 'Your Challenges',
    href: '/preferred-event',
    icon: Goal,
  },
  {
    title: 'Stats',
    href: '/stats',
    icon: ChartSpline,
  },
  {
    title: 'Trophy Case',
    href: '/trophy-case',
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
    icon: UserPlus,
  },
  {
    title: 'Tutorials',
    href: '/tutorials',
    icon: Handshake,
  },
];

// Virtual race specific nav items
const virtualRaceNavItems: NavItem[] = [
  {
    title: 'Home',
    href: '/dashboard',
    icon: Home,
  },
  {
    title: 'Manage Races',
    href: '/virtual-races',
    icon: Wrench,
  },
  {
    title: 'Schedule a Race',
    href: '/virtual-races/create',
    icon: CalendarPlus,
  },
  {
    title: 'Goals',
    href: '/goals',
    icon: Goal,
  },
  {
    title: 'Trophy Case',
    href: '/trophy-case',
    icon: Trophy,
  },
];

// Fit Life specific nav items
const fitLifeNavItems: NavItem[] = [
  {
    title: 'Home',
    href: '/dashboard',
    icon: Home,
  },
  {
    title: 'Schedule Quest',
    href: '/fit-life/activities/create',
    icon: CalendarPlus,
  },
  {
    title: 'Manage Quests',
    href: '/fit-life/activities',
    icon: Wand2,
  },
  {
    title: 'Quests History',
    href: '/fit-life/activities/history',
    icon: Archive,
  },
  {
    title: "Bard's Tale",
    href: '/fit-life/stats',
    icon: Star,
  },
  {
    title: 'Journal',
    href: '/fit-life/journal',
    icon: BookImage,
  },
  {
    title: 'Armory',
    href: '/fit-life/armory',
    icon: Trophy,
  },
  {
    title: 'Tutorials',
    href: '/tutorials',
    icon: Handshake,
  },
];

// Promotional event nav items
const promotionalNavItems: NavItem[] = [
  {
    title: 'Stats',
    href: '/stats',
    icon: ChartSpline,
  },
  {
    title: 'Trophy Case',
    href: '/trophy-case',
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
    icon: UserPlus,
  },
  {
    title: 'Tutorials',
    href: '/tutorials',
    icon: Handshake,
  },
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

  // Get navigation items based on preferred event type
  const getNavItemsForEventType = (): NavItem[] => {
    const preferredEvent = auth.preferred_event;
    const preferredEventType = preferredEvent?.event_type;

    // If no preferred event, show standard items
    if (!preferredEventType) {
      return mainNavItems;
    }

    // Return appropriate nav items based on event type
    switch (preferredEventType) {
      case 'race':
        // Virtual race events show specialized race management pages
        return virtualRaceNavItems;

      case 'fit_life':
        // Fit Life events show specialized fitness activity pages
        return fitLifeNavItems;

      case 'promotional':
        // Promotional events show limited pages
        // Note: Could add additional filtering here based on event name
        // e.g., hiding Trophy Case for events with "Streaker" in the name
        return promotionalNavItems;

      case 'regular':
      case 'month':
      default:
        // Regular and monthly events show all standard pages
        return mainNavItems;
    }
  };

  const navItems = getNavItemsForEventType();

  return (
    <Sidebar collapsible="icon" variant="inset">
      <SidebarHeader>
        <EventSwitcher />
      </SidebarHeader>

      <SidebarContent>
        <NavMain items={navItems} className="[&_[data-active=true]]:bg-primary [&_[data-active=true]]:text-white" />
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
