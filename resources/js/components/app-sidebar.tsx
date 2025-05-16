import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { CalendarDays, ChartSpline, ChevronDown, Handshake, Trophy, Users, Settings, UserCog } from 'lucide-react';
import AppLogo from './app-logo';

const userNavItems: NavItem[] = [
    {
        title: 'Home',
        href: '/dashboard',
        icon: CalendarDays,
    },
    {
        title: 'Stats',
        href: '/',
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
        href: '/',
        icon: Users,
    },
    {
        title: 'Tutorials',
        href: '/',
        icon: Handshake,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/admin/dashboard',
        icon: Settings,
    },
    {
        title: 'Manage Users',
        href: '/admin/users',
        icon: UserCog,
    },
    {
        title: 'Manage Events',
        href: '/admin/events',
        icon: CalendarDays,
    },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { auth } = usePage().props as any;
    const { currentEvent } = usePage().props as any;

    // Determine which menu items to show based on user role
    const mainNavItems = auth.is_admin ? adminNavItems : userNavItems;

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
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
            </SidebarFooter>
        </Sidebar>
    );
}
