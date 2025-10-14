import PageContent from '@/components/atoms/page-content';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

const sidebarNavItems: NavItem[] = [
  {
    title: 'Profile',
    href: '/settings/profile',
    icon: null,
  },
  {
    title: 'Password',
    href: '/settings/password',
    icon: null,
  },
  {
    title: 'Device Syncing',
    href: '/settings/device-sync',
    icon: null,
  },
  {
    title: 'Manual Entry',
    href: '/settings/manual-entry',
    icon: null,
  },
  {
    title: 'Privacy',
    href: '/settings/privacy',
    icon: null,
  },
  {
    title: 'RTY Goals',
    href: '/settings/rty-goals',
    icon: null,
  },
  {
    title: 'Appearance',
    href: '/settings/appearance',
    icon: null,
  },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
  // When server-side rendering, we only render the layout on the client...
  if (typeof window === 'undefined') {
    return null;
  }

  const { auth } = usePage<SharedData>().props;
  const currentPath = window.location.pathname;

  // Filter menu items based on event group
  const visibleNavItems = sidebarNavItems.filter((item) => {
    if (item.title === 'RTY Goals') {
      return auth?.preferred_event?.event_group === 'rty';
    }
    return true;
  });

  return (
    <PageContent>
      <div>
        <Heading title="Settings" description="Manage your profile and account settings" />
        <div className="flex flex-col space-y-8 lg:flex-row lg:space-y-0 lg:space-x-12">
          <aside className="w-full max-w-xl lg:w-48">
            <nav className="flex flex-col space-y-1 space-x-0">
              {visibleNavItems.map((item) => (
                <Button
                  key={item.href}
                  size="sm"
                  variant="link"
                  asChild
                  className={cn('w-full justify-start text-black dark:text-white', {
                    'bg-primary text-white': currentPath === item.href,
                  })}
                >
                  <Link href={item.href} prefetch>
                    {item.title}
                  </Link>
                </Button>
              ))}
            </nav>
          </aside>

          <Separator className="my-6 md:hidden" />

          <div className="w-full flex-1">
            <section className="w-full space-y-12">{children}</section>
          </div>
        </div>
      </div>
    </PageContent>
  );
}
