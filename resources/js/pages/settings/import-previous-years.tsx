import { Head } from '@inertiajs/react';
import { useState } from 'react';

import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
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
  const [selectedYear, setSelectedYear] = useState<number | null>(null);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Import Previous Years" />

      <SettingsLayout>
        <div className="space-y-6">
          <HeadingSmall
            title="Import Previous Years"
            description="Type in the email address you used to log in to each year’s tracker. If you cannot remember your email or if you need to edit your miles you can choose to manually enter them. Once you have imported your miles you can view your yearly data on your 'Stats' page."
          />
        </div>

        <div className="space-y-6">
          <HeadingSmall
            title="2020"
            description="If you are new to RTE and did not use the Tracker in 2020, you can manually enter your mileage for 2020 to compare your progress in 2021."
          />
          <Dialog>
            <DialogTrigger asChild>
              <Button variant="outline" className="bg-primary text-white" onClick={() => setSelectedYear(2020)}>
                2020 Manual Entry
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Run The Year 2020 Miles Manual Entry</DialogTitle>
                <DialogDescription>Choose how to enter your miles:</DialogDescription>
              </DialogHeader>
              <div className="mt-4 flex flex-col gap-4">
                <Button variant="outline">Enter Year’s Total Miles</Button>
                <Button variant="outline">Enter Miles By Month</Button>
              </div>
            </DialogContent>
          </Dialog>
        </div>

        <div className="space-y-6">
          <HeadingSmall
            title="2019"
            description="If you are new to RTE and did not use the Tracker in 2019, you can manually enter your mileage for 2019 to compare your progress in 2020."
          />
          <Dialog>
            <DialogTrigger asChild>
              <Button variant="outline" className="bg-primary text-white" onClick={() => setSelectedYear(2019)}>
                2019 Manual Entry
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Run The Year 2019 Miles Manual Entry</DialogTitle>
                <DialogDescription>Choose how to enter your miles:</DialogDescription>
              </DialogHeader>
              <div className="mt-4 flex flex-col gap-4">
                <Button variant="outline">Enter Year’s Total Miles</Button>
                <Button variant="outline">Enter Miles By Month</Button>
              </div>
            </DialogContent>
          </Dialog>
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}
