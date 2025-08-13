import EventBannerImage from '@/components/atoms/EventBannerImage';
import NextBib from '@/components/partials/NextBib';
import ProgressCard from '@/components/partials/ProgressCard';
import { Calendar } from '@/components/ui/calendar';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import MilesToNextBib from '@/components/partials/MilesToNextBib';
import { Button } from '@/components/ui/button';
import { User, Users } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: route('dashboard'),
  },
];

export default function Dashboard() {
  const { auth } = usePage<SharedData>().props;
  const [date, setDate] = useState<Date>(new Date());
  const [dataFor, setDataFor] = useState('you');
  const [userGoal, setUserGoal] = useState(0);

  // Get the event goal from user settings
  useEffect(function() {
    const eventSlug = auth.preferred_event.name.toLowerCase().replace(/ /g, '-');
    const userSettings = JSON.parse(auth.user.settings as string);
    const rtyGoals = userSettings.rty_goals || [];
    const eventGoal = rtyGoals.find((goal: bigint) => goal[eventSlug]) || {};
    const goal = eventGoal[eventSlug] || 0;
    setUserGoal(goal);
  }, [auth.preferred_event.name, auth.user.settings])

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Home" />
      <div className="flex flex-col gap-10 p-4">
        <EventBannerImage />
        <div className="flex flex-col md:flex-row justify-between gap-5">
          <div className="page-title">
            <h1 className="text-2xl md:text-3xl lg:text-4xl font-semibold">
              {auth.user.display_name}'s {auth.preferred_event.name} Journey
            </h1>
          </div>
          <div className="flex gap-2">
            <div className="flex gap-2">
              <Button
                variant={dataFor === 'you' ? 'default' : 'secondary'}
                onClick={() => setDataFor('you')}
              >
                <User /> You
              </Button>
              <Button
                variant={dataFor === 'team' ? 'default' : 'secondary'}
                onClick={() => setDataFor('team')}
              >
                <Users /> Team
              </Button>
            </div>
          </div>
        </div>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <ProgressCard totalPoints={auth.total_points} goal={userGoal} title="Your Progress" />
          <NextBib />
          <MilesToNextBib />
        </div>
        <Calendar date={date} setDate={setDate} />
      </div>
    </AppLayout>
  );
}
