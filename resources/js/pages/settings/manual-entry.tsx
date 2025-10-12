import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import HeadingSmall from '@/components/heading-small';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import axios from 'axios';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: route('dashboard'),
  },
  {
    title: 'Settings',
    href: route('profile.edit'),
  },
  {
    title: 'Manual Entry',
    href: '/settings/manual-entry',
  },
];

export default function ManualEntry() {
  const [isGlobalManualEntry, setIsGlobalManualEntry] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    // Fetch current setting
    axios
      .get(route('manual-entry.get'))
      .then((response) => {
        setIsGlobalManualEntry(response.data.manual_entry_global);
        setIsLoading(false);
      })
      .catch((error) => {
        console.error('Error fetching manual entry settings:', error);
        toast.error('Failed to load manual entry settings');
        setIsLoading(false);
      });
  }, []);

  const handleToggle = (checked: boolean) => {
    setIsSaving(true);

    axios
      .post(route('manual-entry.update'), {
        manual_entry_global: checked,
      })
      .then((response) => {
        setIsGlobalManualEntry(checked);
        toast.success(response.data.message);
        setIsSaving(false);
      })
      .catch((error) => {
        console.error('Error updating manual entry settings:', error);
        toast.error('Failed to update manual entry settings');
        setIsSaving(false);
      });
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Manual Entry" />

      <SettingsLayout>
        <div className="space-y-6">
          <HeadingSmall title="Manual Entry" description="Configure how manual entries are applied across your challenges" />
        </div>
        <div className="space-y-6">
          <p>
            Would you like to have manually entered miles be added/updated to every challenge you are currently in? For example, if you are in both
            Amerithon and RTY you can manually enter miles in one challenge and they will be automatically added to the other. Any edits you make to
            one will be reflected in all challenges. Select "Yes" if you want manual entries and changes to apply to all challenges you are in. Select
            "No" if you would rather enter your miles manually in each challenge separately.
          </p>
          <div className="flex items-center space-x-2">
            <Switch id="enable-manual-entry" checked={isGlobalManualEntry} onCheckedChange={handleToggle} disabled={isLoading || isSaving} />
            <Label htmlFor="enable-manual-entry">Make Manual Entry Global?</Label>
          </div>
          {isSaving && <p className="text-muted-foreground text-sm">Saving...</p>}
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}
