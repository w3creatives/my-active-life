import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Skeleton } from '@/components/ui/skeleton';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { Target, TrendingUp } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface TargetData {
  current_distance: number;
  target_goal: number;
  on_target_mileage: number;
  on_target_percentage: number;
  days_remaining: number;
  daily_average_needed: number;
  current_daily_average: number;
  is_on_track: boolean;
  goal_indicator: string;
  goal_message: string;
  event_end_date: string;
  estimated_completion_date: string;
}

interface AreYouOnTargetProps {
  dataFor?: string;
}

export default function AreYouOnTarget({ dataFor = 'you' }: AreYouOnTargetProps) {
  const { auth } = usePage<SharedData>().props;
  const [loading, setLoading] = useState(true);
  const [targetData, setTargetData] = useState<TargetData | null>(null);

  useEffect(() => {
    const fetchTargetData = async () => {
      setLoading(true);
      try {
        const routeName = dataFor === 'team' ? 'teamstats' : 'userstats';
        const response = await axios.get(route(routeName, ['target']), {
          params: {
            event_id: auth.preferred_event.id,
            user_id: auth.user.id,
          },
        });
        setTargetData(response.data);
        setLoading(false);
      } catch (err) {
        console.error('Error fetching target data:', err);
        setLoading(false);
      }
    };

    fetchTargetData();
  }, [dataFor]);

  const targetStats = useMemo(() => {
    if (!targetData) {
      return {
        statusColor: 'gray',
        statusText: 'No data',
        gaugeColor: 'hsl(var(--muted))',
      };
    }

    let statusColor = 'gray';
    let statusText = 'No Progress';
    let gaugeColor = 'hsl(var(--muted))';

    // Use the indicator from the mobile API logic
    switch (targetData.goal_indicator) {
      case 'behind':
        statusColor = 'red';
        statusText = 'Behind Target';
        gaugeColor = 'hsl(var(--destructive))';
        break;
      case 'nearly there':
        statusColor = 'yellow';
        statusText = 'Nearly There';
        gaugeColor = 'hsl(45, 93%, 58%)'; // Orange/yellow
        break;
      case 'on target':
        statusColor = 'green';
        statusText = 'On Target';
        gaugeColor = 'hsl(var(--primary))';
        break;
      case 'ahead':
        statusColor = 'green';
        statusText = 'Ahead of Target';
        gaugeColor = 'hsl(142, 76%, 36%)'; // Green
        break;
      default:
        statusColor = 'gray';
        statusText = 'No Progress';
        gaugeColor = 'hsl(var(--muted))';
    }

    return {
      statusColor,
      statusText,
      gaugeColor,
    };
  }, [targetData]);

  const formatDistance = (distance: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(distance);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  };

  return (
    <>
      {loading ? (
        <Card>
          <CardHeader>
            <Skeleton className="mb-2 h-6 w-full" />
            <Skeleton className="h-4 w-full" />
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <Skeleton className="h-8 w-full" />
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Skeleton className="h-4 w-full" />
                  <Skeleton className="h-6 w-full" />
                </div>
                <div className="space-y-2">
                  <Skeleton className="h-4 w-full" />
                  <Skeleton className="h-6 w-full" />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Skeleton className="h-4 w-full" />
                  <Skeleton className="h-6 w-full" />
                </div>
                <div className="space-y-2">
                  <Skeleton className="h-4 w-full" />
                  <Skeleton className="h-6 w-full" />
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      ) : (
        <Card>
          <CardHeader className="pb-3">
            <div className="flex items-center gap-2">
              <Target className={`size-5 ${targetStats.statusColor === 'green' ? 'text-green-500' :
                targetStats.statusColor === 'red' ? 'text-red-500' : 'text-gray-500'
                }`} />
              <CardTitle className="text-lg">Are You On Target?</CardTitle>
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            {/* Status Badge */}
            {targetData && (<div className="space-y-3">
              <div className="flex items-center justify-end text-sm">
                <span className="font-medium">{targetData.on_target_percentage.toFixed(2)}%</span>
              </div>
              <Progress value={targetData.on_target_percentage} className="h-3" />
              <div className="flex items-center justify-between">
                <Badge
                  variant={targetStats.statusColor === 'green' ? 'default' : 'secondary'}
                  className={`${targetStats.statusColor === 'green' ? 'bg-green-500 hover:bg-green-600' :
                    targetStats.statusColor === 'red' ? 'bg-red-500 hover:bg-red-600' :
                      targetStats.statusColor === 'yellow' ? 'bg-yellow-500 hover:bg-yellow-600' :
                        'bg-gray-500 hover:bg-gray-600'
                    } text-white`}
                >
                  {targetStats.statusText}
                </Badge>
                {targetData && (
                  <div className="text-sm text-muted-foreground">
                    {targetData.days_remaining.toFixed(2)} days left
                  </div>
                )}
              </div>
            </div>)}
            {/* Gauge Chart */}
            {/*targetData && (
              <div className="flex justify-center">
                <GaugeChart
                  value={targetData.on_target_percentage}
                  max={150} // Show up to 150% so we can see overachievement
                  color={targetStats.gaugeColor}
                  size={200}
                  showPercentage={true}
                  label="On Target"
                  description={`${formatDistance(targetData.current_distance)} of ${formatDistance(targetData.on_target_mileage)} expected miles`}
                />
              </div>
            ) */}

            {/* Stats Grid */}
            {targetData && (
              <div className="grid grid-cols-2 gap-4">
                <div className="bg-muted/30 rounded-lg border p-3 text-center">
                  <div className="text-lg font-bold text-primary">
                    {formatDistance(targetData.current_daily_average)}
                  </div>
                  <div className="text-muted-foreground text-xs">Current Daily Avg</div>
                </div>
                <div className="bg-muted/30 rounded-lg border p-3 text-center">
                  <div className="text-lg font-bold">
                    {formatDistance(targetData.daily_average_needed)}
                  </div>
                  <div className="text-muted-foreground text-xs">Needed Daily Avg</div>
                </div>
              </div>
            )}

            {/* Goal Message */}
            {targetData && targetData.goal_message && (
              <div className="bg-muted/30 rounded-lg border p-4 text-center">
                <p className="text-sm text-foreground">{targetData.goal_message}</p>
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </>
  );
}
