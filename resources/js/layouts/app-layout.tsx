import { Toaster } from '@/components/ui/sonner';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs, ...props }: AppLayoutProps) => (
    <div className="bg-background min-h-screen">
        <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
            <div className="mx-auto w-full max-w-6xl">{children}</div>
        </AppLayoutTemplate>
        <Toaster />
    </div>
);
