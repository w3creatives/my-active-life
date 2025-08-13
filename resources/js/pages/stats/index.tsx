import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

import EventBannerImage from '@/components/atoms/EventBannerImage';
import AchievementsCards from '@/components/stats/achievements-cards';
import BasicStatsCard from '@/components/stats/basic-stats-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Last30days from '@/pages/stats/components/last30days';
import MonthlyPoints from '@/pages/stats/components/monthlyPoints';
import ProgressCard from '@/pages/stats/components/progressCard';
import { User, Users } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: route('dashboard'),
  },
  {
    title: 'Stats',
    href: route('stats'),
  },
];

export default function Stats() {
  const { auth } = usePage<SharedData>().props;
  const [dataFor, setDataFor] = useState('you');

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Stats" />
      <div className="flex flex-col gap-6 p-4">
        <EventBannerImage />
        <div className="flex justify-between gap-5">
          <div className="page-title">
            <h1 className="text-4xl font-normal">
              {auth.user.display_name}'s {auth.preferred_event.name} Journey
            </h1>
          </div>
          <div className="flex gap-2">
            <Button variant={dataFor === 'you' ? 'default' : 'secondary'} onClick={() => setDataFor('you')}>
              <User /> You
            </Button>
            <Button variant={dataFor === 'team' ? 'default' : 'secondary'} onClick={() => setDataFor('team')}>
              <Users /> Team
            </Button>
          </div>
        </div>

        <div className="grid grid-cols-3 gap-5">
          <ProgressCard />

          <Card>
            <CardHeader>
              <CardTitle className="text-2xl">Your Next Bib</CardTitle>
              <CardDescription></CardDescription>
            </CardHeader>
            <CardContent className="space-y-2">
              <div className="text-4xl font-bold">1,234.56</div>
              <div className="text-muted-foreground">
                <p>Miles completed in this year</p>
              </div>
              <div className="mt-6 rounded-lg p-4 shadow-sm">
                <h4 className="text-muted-foreground text-sm">Next Bib</h4>
                <div className="flex items-center gap-5">
                  <img src="/static/Logo-Amerithon.png" className="size-25 object-contain" alt="" />
                  <h3 className="text-2xl font-semibold">RTY 2025 Mile 1500</h3>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-2xl">Are You On Target?</CardTitle>
              <CardDescription></CardDescription>
            </CardHeader>
            <CardContent className="space-y-2">
              <div className="text-4xl font-bold">1,234.56</div>
              <div className="text-muted-foreground space-y-2">
                <p>Nicely done! You are ahead! Even more ice-cream for you! You are predicted to finish approximately on September 26th, 2025.</p>
                <p>Um, let's do the numbers...</p>
                <p>WOW! Looks like this challenge is too easy for you! Sign up for Amerithon right away!</p>
                <p>
                  If you were to decrease your average daily mileage from 7.55 miles per day to 3.39 miles per day, you would still reach your goal on
                  December 31st, 2025.
                </p>
              </div>
            </CardContent>
          </Card>
        </div>

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-1">
          <BasicStatsCard />
          <AchievementsCards />

          <Last30days />
          <MonthlyPoints />
        </div>
      </div>
    </AppLayout>
  );
}
