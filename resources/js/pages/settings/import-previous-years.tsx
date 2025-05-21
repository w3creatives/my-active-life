import { Head } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Import Previous Years',
    href: '/settings/import-previous-years',
  },
];

export default function ImportPreviousYears() {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Import Previous Years" />

      <SettingsLayout>
        <div className="space-y-6">
          <HeadingSmall title="Import Previous Years" />
        </div>
        <div className="space-y-6">
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}
