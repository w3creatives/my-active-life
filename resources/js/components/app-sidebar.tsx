import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { CalendarDays, ChartSpline, ChevronDown, Handshake, Trophy, Users, Settings, UserCog } from 'lucide-react';
import AppLogo from './app-logo';

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
        href: '/',
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
    const { currentEvent } = usePage().props as any;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <div className="flex items-center gap-2">
                    <AppLogo />
                </div>

                <SidebarMenu>
                    <SidebarMenuItem>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <SidebarMenuButton>
                                    {currentEvent?.name || 'Select Event'}
                                    <ChevronDown className="ml-auto" />
                                </SidebarMenuButton>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-[--radix-popper-anchor-width]">
                                {auth.participations?.map((participation: any) => (
                                    <DropdownMenuItem key={participation.event.id} asChild>
                                        <Link href="" as="button" className="w-full">
                                            <span>{participation.event.name}</span>
                                        </Link>
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </SidebarMenuItem>
                </SidebarMenu>
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
