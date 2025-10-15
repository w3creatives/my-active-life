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
import { User, Users } from 'lucide-react';
import { useState } from 'react';
import MemberStat from './components/MemberStat';
import PageContent from '@/components/atoms/page-content';
import PageTitle from '@/components/atoms/PageTitle';

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
  const { auth, team, eventProgress, nextMilestone } = usePage<StatsPageProps>().props;
  const teamData = team as { name?: string } | null;

  const [dataFor, setDataFor] = useState('you');

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Stats" />
      <PageContent>
        <div className="flex justify-between gap-5">
          <div className="page-title">
            <PageTitle title={`${auth.user.display_name}'s ${auth.preferred_event.name} Journey ${teamData?.name}`} />
          </div>
          {teamData && (
            <div className="flex gap-2">
              <Button variant={dataFor === 'you' ? 'default' : 'secondary'} onClick={() => setDataFor('you')}>
                <User /> You
              </Button>
              <Button variant={dataFor === 'team' ? 'default' : 'secondary'} onClick={() => setDataFor('team')}>
                <Users /> Team
              </Button>
            </div>
          )}
        </div>

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
          <EventProgressGauge dataFor={dataFor} />

          <NextMilestone dataFor={dataFor} />

          <AreYouOnTarget dataFor={dataFor} />
        </div>

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-1">
          {/* Conditionally show Amerithon Map for Amerithon events */}
          {auth.preferred_event.name.toLowerCase().includes('amerithon') && <AmerithonMap dataFor={dataFor} />}

          <Last30days dataFor={dataFor} />
        </div>
        
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_2fr]">
          <PersonalBests dataFor={dataFor} />
          <MonthlyPoints dataFor={dataFor} />
        </div>

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-1">
          {dataFor == 'team' && <MemberStat />}
          {/* Conditionally show Activity Type breakdown for Amerithon and JOGLE events */}
          {(auth.preferred_event.name.toLowerCase().includes('amerithon') || auth.preferred_event.name.toLowerCase().includes('jogle')) && (
            <MileageByActivityType dataFor={dataFor} />
          )}
        </div>
      </PageContent>
    </AppLayout>
  );
}
