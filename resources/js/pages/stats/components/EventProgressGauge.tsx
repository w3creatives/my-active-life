import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Target, TrendingUp, Trophy } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';
import { Skeleton } from '@/components/ui/skeleton';

interface EventProgressGaugeProps {
  className?: string;
  dataFor?: string;
}
interface ProgressData {
    eventName: string;
    totalDistance: number;
    coveredDistance: number;
    userGoal?: number;
}

export default function EventProgressGauge({ className = '', dataFor = 'you' }: EventProgressGaugeProps) {
 /* const { percentage, remainingDistance, isCompleted, goalPercentage } = useMemo(() => {
    const percentage = Math.min((coveredDistance / totalDistance) * 100, 100);
    const remainingDistance = Math.max(totalDistance - coveredDistance, 0);
    const isCompleted = coveredDistance >= totalDistance;
    const goalPercentage = userGoal ? Math.min((coveredDistance / userGoal) * 100, 100) : null;

    return { percentage, remainingDistance, isCompleted, goalPercentage };
  }, [coveredDistance, totalDistance, userGoal]);*/
    const { auth } = usePage<SharedData>().props;
    const [loading, setLoading] = useState(true);
    const [data, setData] = useState<ProgressData | null>(null);

    const fetchData = async () => {
        setLoading(true);
        try {
            const routeName = dataFor === 'team' ? 'teamstats' : 'userstats';
            const response = await axios.get(route(routeName, ['progress']), {
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

  return (
      <>
          {loading ? (
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
          ):(
              <Card className={`${className}`}>
                  <CardHeader className="pb-3">
                      <div className="flex items-center justify-between">
                          <CardTitle className="flex items-center gap-2 text-xl">
                              <Trophy className="text-primary h-5 w-5" />
                              {data.eventName} Progress
                          </CardTitle>
                          {data.isCompleted && (
                              <Badge variant="default" className="bg-green-500">
                                  <Trophy className="mr-1 h-3 w-3" />
                                  Complete!
                              </Badge>
                          )}
                      </div>
                      <CardDescription>Your journey through {data.eventName}</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-6">
                      {/* Main Progress */}
                      <div className="space-y-3">
                          <div className="flex items-center justify-between text-sm">
            <span className="flex items-center gap-1">
              <Target className="text-muted-foreground h-4 w-4" />
              Event Progress
            </span>
                              <span className="font-medium">{data.percentage}%</span>
                          </div>
                          <Progress value={data.percentage} className="h-3" />
                          <div className="grid grid-cols-2 gap-4 text-sm">
                              <div>
                                  <p className="text-muted-foreground">Completed</p>
                                  <p className="text-primary text-2xl font-bold">{formatDistance(data.coveredDistance)}</p>
                                  <p className="text-muted-foreground text-xs">miles</p>
                              </div>
                              <div>
                                  <p className="text-muted-foreground">Remaining</p>
                                  <p className="text-2xl font-bold">{formatDistance(data.remainingDistance)}</p>
                                  <p className="text-muted-foreground text-xs">miles</p>
                              </div>
                          </div>
                      </div>

                      {/* User Goal Progress (if set) */}
                      {data.userGoal && data.goalPercentage !== null && (
                          <div className="space-y-3 border-t pt-4">
                              <div className="flex items-center justify-between text-sm">
              <span className="flex items-center gap-1">
                <TrendingUp className="text-muted-foreground h-4 w-4" />
                Personal Goal
              </span>
                                  <span className="font-medium">{data.goalPercentage}%</span>
                              </div>
                              <Progress value={data.goalPercentage} className="h-2" />
                              <div className="text-muted-foreground flex justify-between text-sm">
                                  <span>{formatDistance(data.coveredDistance)} miles</span>
                                  <span>Goal: {formatDistance(data.userGoal)} miles</span>
                              </div>
                              {data.coveredDistance >= data.userGoal && (
                                  <Badge variant="default" className="w-fit bg-green-500">
                                      <Trophy className="mr-1 h-3 w-3" />
                                      Goal Achieved!
                                  </Badge>
                              )}
                          </div>
                      )}

                      {/* Event Total Information */}
                      <div className="border-t pt-4">
                          <div className="grid grid-cols-1 text-center">
                              <div>
                                  <p className="text-muted-foreground text-xs tracking-wide uppercase">Event Total Distance</p>
                                  <p className="text-lg font-bold">{formatDistance(data.totalDistance)} miles</p>
                              </div>
                          </div>
                      </div>
                  </CardContent>
              </Card>
          )}
      </>
  );
}
