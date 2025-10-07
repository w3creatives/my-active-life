import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

import EventBannerImage from '@/components/atoms/EventBannerImage';
import { Button } from '@/components/ui/button';
import AmerithonMap from '@/pages/stats/components/AmerithonMap';
import AreYouOnTarget from '@/pages/stats/components/AreYouOnTarget';
import EventProgressGauge from '@/pages/stats/components/EventProgressGauge';
import Last30days from '@/pages/stats/components/last30days';
import MileageByActivityType from '@/pages/stats/components/MileageByActivityType';
import MonthlyPoints from '@/pages/stats/components/monthlyPoints';
import NextMilestone from '@/pages/stats/components/NextMilestone';
import PersonalBests from '@/pages/stats/components/PersonalBests';
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

interface EventProgress {
  eventName: string;
  totalDistance: number;
  coveredDistance: number;
  userGoal?: number;
}

interface NextMilestone {
  id: number;
  name: string;
  distance: number;
  description?: string;
  logo?: string;
  data?: any;
  userDistance: number;
  previousMilestoneDistance: number;
  eventName: string;
}

interface StatsPageProps extends SharedData {
  eventProgress: EventProgress;
  nextMilestone: NextMilestone | null;
}

export default function Stats() {
  const { auth, eventProgress, nextMilestone } = usePage<StatsPageProps>().props;
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

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
          <EventProgressGauge
            dataFor={dataFor}
          />

          <NextMilestone
            milestone={nextMilestone}
            userDistance={nextMilestone?.userDistance || eventProgress.coveredDistance}
            previousMilestoneDistance={nextMilestone?.previousMilestoneDistance || 0}
            eventName={nextMilestone?.eventName || eventProgress.eventName}
            dataFor={dataFor}
          />

          <AreYouOnTarget dataFor={dataFor} />
        </div>

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-1">
          {/*<BasicStatsCard />*/}
          {/*<AchievementsCards />*/}

          {/* Conditionally show Amerithon Map for Amerithon events */}
          {auth.preferred_event.name.toLowerCase().includes('amerithon') && <AmerithonMap dataFor={dataFor} />}

          <Last30days dataFor={dataFor} />

          <div className="grid grid-cols-1 gap-6 lg:grid-cols-[40%_60%]">
            <PersonalBests dataFor={dataFor} />
            <MonthlyPoints dataFor={dataFor} />
          </div>

          {/* Conditionally show Activity Type breakdown for Amerithon and JOGLE events */}
          {(auth.preferred_event.name.toLowerCase().includes('amerithon') || auth.preferred_event.name.toLowerCase().includes('jogle')) && (
            <MileageByActivityType dataFor={dataFor} />
          )}
        </div>
      </div>
    </AppLayout>
  );
}
