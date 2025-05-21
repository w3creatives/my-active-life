import { Head } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'RTY Goals',
    href: '/settings/rty-goals',
  },
];

export default function RtyGoals() {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="RTY Goals" />

      <SettingsLayout>
        <div className="space-y-6">
          <HeadingSmall title="RTY Goals" />
        </div>
        <div className="space-y-6">
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}
