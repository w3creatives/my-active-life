import { Head } from '@inertiajs/react';
import { useState } from 'react';

import HeadingSmall from '@/components/heading-small';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Tracker Attitude',
    href: '/settings/rty-goals',
  },
];

const attitudes = ['Relaxed', 'Yoda', 'Tough Love', 'Positive', 'Cheerleader', 'Sci-Fi', 'Historian', 'Super Hero'];

export default function TrackerAttitude() {
  const [attitude, setAttitude] = useState('Relaxed'); // default selection

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Tracker Attitude" />
      <SettingsLayout>
        <div className="space-y-6">
          <HeadingSmall
            title="Tracker Attitude"
            description="Select how you would like to interact with the Tracker. Your choice here will influence the tone of motivational messages to help you work toward your goal all year long."
          />
        </div>

        <div className="space-y-6">
          <Label htmlFor="tracker-attitude">Attitude</Label>
          <Select value={attitude} onValueChange={setAttitude}>
            <SelectTrigger className="w-[180px]">
              <SelectValue placeholder="" />
            </SelectTrigger>
            <SelectContent>
              {attitudes.map((att) => (
                <SelectItem key={att} value={att}>
                  {att}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}
