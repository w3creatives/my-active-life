import { Head } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Tracker Attitude',
    href: '/settings/rty-goals',
  },
];

export default function TrackerAttitude() {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Tracker Attitude" />

      <SettingsLayout>
        <div className="space-y-6">
          <HeadingSmall title="Tracker Attitude" />
        </div>
        <div className="space-y-6">
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}
