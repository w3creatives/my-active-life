import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { ExternalLink, Image, MapPin, Target } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { usePage } from '@inertiajs/react';
import { SharedData } from '@/types';
import { Skeleton } from '@/components/ui/skeleton';

interface Milestone {
  id: number;
  name: string;
  distance: number;
  description?: string;
  logo?: string;
  data?: {
    flyover_url?: string;
    landmark_image?: string;
  };
}

interface NextMilestoneProps {
  milestone: Milestone | null;
  userDistance: number;
  previousMilestoneDistance?: number;
  eventName: string;
  className?: string;
  dataFor?: string;
}

export default function NextMilestone({ milestone, userDistance, previousMilestoneDistance = 0, eventName, className = '', dataFor = 'you' }: NextMilestoneProps) {

    const { auth } = usePage<SharedData>().props;
    const [loading, setLoading] = useState(true);
    const [data, setData] = useState<Milestone | null>(null);

    const fetchData = async () => {
        setLoading(true);
        try {
            const routeName = dataFor === 'team' ? 'teamstats' : 'userstats';
            const response = await axios.get(route(routeName, ['next-milestone']), {
                params: {
                    event_id: auth.preferred_event.id,
                    user_id: auth.user.id,
                },
            });
            setData(response.data);
            setLoading(false);
        } catch (err) {
            console.error('Error fetching data:', err);
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [dataFor]);

  const formatDistance = (distance: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(distance);
  };

  if(loading) {
      return (
          <Card className={`${className}`}>
              <CardHeader className="pb-3">
                  <div className="flex items-center justify-between">
                      <CardTitle className="flex items-center gap-2 text-xl">
                          <Skeleton className="text-lg font-bold h-4 w-full"/>
                          <Skeleton className="h-4 w-full" />
                      </CardTitle>

                  </div>
                  <CardDescription><Skeleton className="h-4 w-full" /></CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                  {/* Main Progress */}
                  <div className="space-y-3">
                      <div className="flex items-center justify-between text-sm">
            <span className="flex items-center gap-1">

               <Skeleton className="h-4 w-full" />
            </span>
                          <span className="font-medium"><Skeleton className="h-4 w-full" /></span>
                      </div>
                      <Skeleton className="h-3 w-full" />
                      <div className="grid grid-cols-2 gap-4 text-sm">
                          <div>
                              <div className="text-muted-foreground"> <Skeleton className="h-4 w-full" /></div>
                              <div className="text-primary text-2xl font-bold"><Skeleton className="h-4 w-full" /></div>
                              <div className="text-muted-foreground text-xs"> <Skeleton className="h-4 w-full" /></div>
                          </div>
                          <div>
                              <div className="text-muted-foreground"> <Skeleton className="h-4 w-full" /></div>
                              <div className="text-2xl font-bold"><Skeleton className="h-4 w-full" /></div>
                              <div className="text-muted-foreground text-xs"> <Skeleton className="h-4 w-full" /></div>
                          </div>
                      </div>
                  </div>

                  {/* Event Total Information */}
                  <div className="border-t pt-4">
                      <div className="grid grid-cols-1 text-center">
                          <div>
                              <div className="text-muted-foreground text-xs tracking-wide uppercase"><Skeleton className="text-lg font-bold h-4 w-full"/></div>
                              <Skeleton className="text-lg font-bold h-4 w-full"/>
                          </div>
                      </div>
                  </div>
              </CardContent>
          </Card>
      );
  }

  if (!data.milestone) {
    return (
      <Card className={`${className}`}>
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-xl">
            <Target className="text-primary h-5 w-5" />
            Your Next Milestone
          </CardTitle>
          <CardDescription>Keep up the great work!</CardDescription>
        </CardHeader>
        <CardContent className="py-8 text-center">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/20">
            <Target className="h-8 w-8 text-green-600 dark:text-green-400" />
          </div>
          <h3 className="mb-2 text-lg font-semibold">All Milestones Completed!</h3>
          <p className="text-muted-foreground">Congratulations! You've reached all milestones in {eventName}.</p>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className={`${className}`}>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <CardTitle className="flex items-center gap-2 text-xl">
            <MapPin className="text-primary h-5 w-5" />
            Your Next Milestone
          </CardTitle>
        </div>
        <CardDescription>Next landmark on your {data.eventName} journey</CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Milestone Info */}
          <div className="space-y-3">
              <div className="flex items-center justify-between text-sm">
                <span className="flex items-center gap-1">
                  <Target className="text-muted-foreground h-4 w-4" />
                    Progress to {data.milestone.name}
                </span><span className="font-medium">{data.segmentProgress.toFixed(2)}%</span>
              </div>
              <Progress value={data.segmentProgress.toFixed(2)} className="h-3" />
              <div className="text-muted-foreground mt-2 flex w-full justify-between text-xs">
                  <span>Mile {formatDistance(data.previousMilestoneDistance)}</span>

                  <span>Mile {formatDistance(data.milestone.distance)}</span>
              </div>
          </div>

          <div className="flex gap-4">
              {data.milestone.logo && (
                  <div className="flex-shrink-0">
                      <img src={data.milestone.logo} alt={data.milestone.name} className="bg-muted h-16 w-16 rounded-lg object-contain p-2" />
                  </div>
              )}
              <div className="min-w-0 flex-1">
                  <h3 className="mb-1 text-xl font-semibold">{data.milestone.name}</h3>
                  <p className="text-muted-foreground mb-2 text-sm">
                      Mile {formatDistance(data.milestone.distance)} of {data.eventName}
                  </p>
                  {data.milestone.description && <p className="text-muted-foreground text-sm">{data.milestone.description}</p>}
              </div>
          </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-2 gap-4 pt-2">
          <div className="bg-muted/50 rounded-lg p-3 text-center">
            <p className="text-primary text-2xl font-bold">{formatDistance(data.coveredDistance)}</p>
            <p className="text-muted-foreground text-xs">Miles Completed</p>
          </div>
          <div className="bg-muted/50 rounded-lg p-3 text-center">
            <p className="text-2xl font-bold">{formatDistance(data.distanceToGo)}</p>
            <p className="text-muted-foreground text-xs">Miles Remaining</p>
          </div>
        </div>

        {/* Action Buttons */}
        {data.milestone.data && (
          <div className="flex gap-2 pt-2">
            {data.milestone.data.flyover_url && (
              <Button variant="outline" size="sm" className="flex-1" asChild>
                <a href={data.milestone.data.flyover_url} target="_blank" rel="noopener noreferrer">
                  <ExternalLink className="mr-1 h-4 w-4" />
                  View Flyover
                </a>
              </Button>
            )}
            {data.milestone.data.landmark_image && (
              <Button variant="outline" size="sm" className="flex-1" asChild>
                <a href={data.milestone.data.landmark_image} target="_blank" rel="noopener noreferrer">
                  <Image className="mr-1 h-4 w-4" />
                  View Image
                </a>
              </Button>
            )}
          </div>
        )}

        {/* Milestone Achievement Preview */}
        {data.distanceToGo < 10 && (
          <div className="bg-primary/5 border-primary/20 rounded-lg border p-3">
            <p className="text-primary mb-1 text-sm font-medium">🎉 Almost there!</p>
            <p className="text-muted-foreground text-xs">
              Just {formatDistance(data.distanceToGo)} miles until you reach {data.milestone.name}!
            </p>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
