import EventBannerImage from '@/components/atoms/EventBannerImage';
import MilesToNextBib from '@/components/partials/MilesToNextBib';
import ProgressCard from '@/components/partials/ProgressCard';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import NextMilestoneCard from '@/components/next-milestone-card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { User, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import axios from 'axios';

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
  const [showTeamView, setShowTeamView] = useState(false);
  const [nextMilestoneData, setNextMilestoneData] = useState<any>(null);
  const [loadingMilestone, setLoadingMilestone] = useState(true);

  console.log(auth.preferred_event);

  if (!auth.preferred_event) {
    router.visit(route('preferred.event'));
    return null;
  }

  // Get the event goal from user settings
  useEffect(
    function () {
      const eventSlug = auth.preferred_event.name.toLowerCase().replace(/ /g, '-');
      const userSettings = JSON.parse(auth.user.settings as string);
      const rtyGoals = userSettings.rty_goals || [];
      const eventGoal = rtyGoals.find((goal: bigint) => goal[eventSlug]) || {};
      const goal = eventGoal[eventSlug] || 0;
      setUserGoal(goal);
    },
    [auth.preferred_event.name, auth.user.settings],
  );

  // Fetch next milestone data
  useEffect(() => {
    const fetchNextMilestone = async () => {
      try {
        const response = await axios.get(route('next.milestone'));
        setNextMilestoneData(response.data);
      } catch (error) {
        console.error('Error fetching next milestone:', error);
      } finally {
        setLoadingMilestone(false);
      }
    };
    
    fetchNextMilestone();
  }, []);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Home" />
      <div className="flex flex-col gap-10 p-4">
        <EventBannerImage />
        <div className="flex flex-col justify-between gap-5 md:flex-row">
          <div className="page-title">
            <h1 className="text-2xl font-semibold md:text-3xl lg:text-4xl">
              {auth.user.display_name}'s {auth.preferred_event.name} Journey
            </h1>
          </div>
          <div className="flex gap-4">
            <div className="flex gap-2">
              <Button variant={dataFor === 'you' ? 'default' : 'secondary'} onClick={() => setDataFor('you')}>
                <User /> You
              </Button>
              <Button variant={dataFor === 'team' ? 'default' : 'secondary'} onClick={() => setDataFor('team')}>
                <Users /> Team
              </Button>
            </div>
            
            {/* Toggle for Bib View */}
            <Card className="w-fit">
              <CardContent className="flex items-center space-x-3 p-4">
                <Label htmlFor="bib-view" className="text-sm font-medium">
                  Personal
                </Label>
                <Switch id="bib-view" checked={showTeamView} onCheckedChange={setShowTeamView} />
                <Label htmlFor="bib-view" className="text-sm font-medium">
                  Team
                </Label>
              </CardContent>
            </Card>
          </div>
        </div>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <ProgressCard totalPoints={auth.total_points} goal={userGoal} title="Your Progress" />
          {!auth.preferred_event.name.toLowerCase().includes('amerithon') && (
            <>
              {loadingMilestone ? (
                <Card>
                  <CardContent className="p-6">
                    <div className="animate-pulse space-y-4">
                      <div className="h-4 bg-muted rounded w-1/2 mx-auto"></div>
                      <div className="h-16 bg-muted rounded mx-auto w-16"></div>
                      <div className="h-4 bg-muted rounded w-3/4 mx-auto"></div>
                      <div className="h-2 bg-muted rounded"></div>
                    </div>
                  </CardContent>
                </Card>
              ) : (
                <NextMilestoneCard
                  nextMilestone={nextMilestoneData?.next_milestone}
                  currentDistance={nextMilestoneData?.current_distance || 0}
                  previousMilestone={nextMilestoneData?.previous_milestone}
                  eventName={nextMilestoneData?.event_name || ''}
                  showTeamView={showTeamView}
                />
              )}
              <MilesToNextBib />
            </>
          )}
        </div>
        {auth.preferred_event.name.toLowerCase().includes('amerithon') && (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {loadingMilestone ? (
              <Card>
                <CardContent className="p-6">
                  <div className="animate-pulse space-y-4">
                    <div className="h-4 bg-muted rounded w-1/2 mx-auto"></div>
                    <div className="h-16 bg-muted rounded mx-auto w-16"></div>
                    <div className="h-4 bg-muted rounded w-3/4 mx-auto"></div>
                    <div className="h-2 bg-muted rounded"></div>
                  </div>
                </CardContent>
              </Card>
            ) : (
              <NextMilestoneCard
                nextMilestone={nextMilestoneData?.next_milestone}
                currentDistance={nextMilestoneData?.current_distance || 0}
                previousMilestone={nextMilestoneData?.previous_milestone}
                eventName={nextMilestoneData?.event_name || ''}
                showTeamView={showTeamView}
              />
            )}
            <MilesToNextBib />
          </div>
        )}
        <Calendar date={date} setDate={setDate} showTeamView={showTeamView} />
      </div>
    </AppLayout>
  );
}
