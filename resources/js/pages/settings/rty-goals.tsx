import HeadingSmall from '@/components/heading-small';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'RTY Goals',
    href: '/settings/rty-goals',
  },
];

export default function RtyGoals() {
  const goals = ['500', '1000', '1500'];
  const [goal, setGoal] = useState('');

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="RTY Goals" />

      <SettingsLayout>
        <div className="space-y-6">
          <HeadingSmall title="RTY Goals" description="Run The Year your way! Pick a goal that is right for you!" />
        </div>

        <div className="space-y-6">
          <Label htmlFor="rty-goals">My mileage goal for 2025 Miles in 2025 is:</Label>
          <Select value={goal} onValueChange={setGoal}>
            <SelectTrigger className="w-[180px]">
              <SelectValue placeholder="2025" />
            </SelectTrigger>
            <SelectContent>
              {goals.map((goal) => (
                <SelectItem key={goal} value={goal}>
                  {goal}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <p className="mt-1 text-sm text-gray-500">
            The default settings of RTY only include miles accumulated on your feet, such as running, walking, stepping, etc. You can add extra miles
            here by flipping each switch.
          </p>
          <div className="flex items-center space-x-2">
            <Label htmlFor="enable-walking-miles">I want my biking miles to be included in my totals:</Label>
            <Switch id="enable-walking-miles" />
          </div>

          <div className="flex items-center space-x-2">
            <Label htmlFor="enable-swimming-miles">I want my swimming miles to be included in my totals:</Label>
            <Switch id="enable-swimming-miles" />
          </div>

          <div className="flex items-center space-x-2">
            <Label htmlFor="enable-others-miles">I want my other miles to be included in my totals:</Label>
            <Switch id="enable-others-miles" />
          </div>
          <p className="mt-1 text-sm text-gray-500">
            Note: Miles qualifying as other miles vary by platform. Garmin, Fitbit, Strava, Apple are all different in that regard.
          </p>
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}
