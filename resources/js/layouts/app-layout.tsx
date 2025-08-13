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
      <footer className="my-10 h-10 flex items-center">
        <div className="w-full max-w-6xl flex flex-col-reverse md:flex-row gap-5 items-center justify-between mx-auto text-sm px-4">
          <p className="text-muted-foreground">{new Date().getFullYear()} &copy; Tracker by Run The Edge</p>
          {/* footer menu */}
          <ul className="flex items-center gap-4">
            <li>
              <a href="https://runtheedge.com/policies/privacy-policy/" target="_blank" className="text-muted-foreground">
                Privacy
              </a>
            </li>
            <li>
              <a href="https://runtheedge.com/policies/terms-of-service/" target="_blank" className="text-muted-foreground">
                Terms & Conditions
              </a>
            </li>
            <li>
              <a href="https://shop.runtheedge.com/" target="_blank" className="text-muted-foreground">
                Store
              </a>
            </li>
          </ul>
        </div>
      </footer>
    </AppLayoutTemplate>
    <Toaster />
  </div>
);
