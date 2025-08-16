import { Head } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Manual Entry',
    href: '/settings/manual-entry',
  },
];

export default function ManualEntry() {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Manual Entry" />

      <SettingsLayout>
        <div className="space-y-6">
          <HeadingSmall title="Manual Entry" description="Update your account's appearance settings" />
        </div>
        <div className="space-y-6">
          <p>
            Would you like to have manually entered miles be added/updated to every challenge you are currently in? For example, if you are in both
            Amerithon and RTY you can manually enter miles in one challenge and they will be automatically added to the other. Any edits you make to
            one will be reflected in all challenges. Select “Yes” if you want manual entries and changes to apply to all challenges you are in. Select
            "No" if you would rather enter your miles manually in each challenge separately.
          </p>
          <div className="flex items-center space-x-2">
            <Switch id="enable-manual-entry" />
            <Label htmlFor="enable-manual-entry">Make Manual Entry Global?</Label>
          </div>
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}
