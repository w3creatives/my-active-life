import EventBannerImage from '@/components/atoms/EventBannerImage';
import MilesToNextBib from '@/components/partials/MilesToNextBib';
import ProgressCard from '@/components/partials/ProgressCard';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent } from '@/components/ui/card';
import NextMilestoneCard from '@/components/next-milestone-card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';
import PageContent from '@/components/atoms/page-content';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: route('dashboard'),
  },
];

export default function Dashboard() {
  const { auth } = usePage<SharedData>().props;
  const [date, setDate] = useState<Date>(new Date());
  const [userGoal, setUserGoal] = useState(0);
  const [totalPoints, setTotalPoints] = useState<number>(auth.total_points ?? 0);
  const [showTeamView, setShowTeamView] = useState(false);
  const [nextMilestoneData, setNextMilestoneData] = useState<any>(null);
  const [loadingMilestone, setLoadingMilestone] = useState(true);
  const [modalities, setModalities] = useState([]);

  if (!auth.preferred_event) {
    router.visit(route('preferred.event'));
    return null;
  }

    const fetchUserEventModality = async () => {
        const response = await axios.get(route('user.event.modalities'));
        setModalities(response.data);
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
      fetchUserEventModality();
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

  // Listen for global points updates to refresh milestones
  useEffect(() => {
    const handler = async () => {
      try {
        // Refresh next milestone card
        setLoadingMilestone(true);
        const milestoneResponse = await axios.get(route('next.milestone'));
        setNextMilestoneData(milestoneResponse.data);

        // Update total points from milestone response if available
        if (milestoneResponse.data?.current_distance !== undefined) {
          setTotalPoints(milestoneResponse.data.current_distance);
        }
      } catch (error) {
        console.error('Error refreshing after points update:', error);
      } finally {
        setLoadingMilestone(false);
      }
    };

    window.addEventListener('points-updated', handler as EventListener);
    return () => window.removeEventListener('points-updated', handler as EventListener);
  }, []);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Home" />
      <PageContent>
        <div className="flex flex-col justify-between gap-5 md:flex-row">
          <div className="page-title">
            <h1 className="text-2xl font-semibold md:text-3xl lg:text-4xl">
              {auth.user.display_name}'s {auth.preferred_event.name} Journey
            </h1>
          </div>
        </div>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <ProgressCard totalPoints={totalPoints} goal={userGoal} title="Your Progress" />
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
          {auth.preferred_event.name.toLowerCase().includes('amerithon') && (
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
        <Calendar date={date} setDate={setDate} showTeamView={showTeamView} modalities={modalities}/>
      </PageContent>
    </AppLayout>
  );
}
