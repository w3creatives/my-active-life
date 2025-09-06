import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Target, TrendingUp, Trophy } from 'lucide-react';
import { useMemo } from 'react';

interface EventProgressGaugeProps {
  eventName: string;
  totalDistance: number;
  coveredDistance: number;
  userGoal?: number;
  className?: string;
  dataFor?: string;
}

export default function EventProgressGauge({ eventName, totalDistance, coveredDistance, userGoal, className = '', dataFor = 'you' }: EventProgressGaugeProps) {
  const { percentage, remainingDistance, isCompleted, goalPercentage } = useMemo(() => {
    const percentage = Math.min((coveredDistance / totalDistance) * 100, 100);
    const remainingDistance = Math.max(totalDistance - coveredDistance, 0);
    const isCompleted = coveredDistance >= totalDistance;
    const goalPercentage = userGoal ? Math.min((coveredDistance / userGoal) * 100, 100) : null;

    return { percentage, remainingDistance, isCompleted, goalPercentage };
  }, [coveredDistance, totalDistance, userGoal]);

  const formatDistance = (distance: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(distance);
  };

  return (
    <Card className={`${className}`}>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <CardTitle className="flex items-center gap-2 text-xl">
            <Trophy className="text-primary h-5 w-5" />
            {eventName} Progress
          </CardTitle>
          {isCompleted && (
            <Badge variant="default" className="bg-green-500">
              <Trophy className="mr-1 h-3 w-3" />
              Complete!
            </Badge>
          )}
        </div>
        <CardDescription>Your journey through {eventName}</CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Main Progress */}
        <div className="space-y-3">
          <div className="flex items-center justify-between text-sm">
            <span className="flex items-center gap-1">
              <Target className="text-muted-foreground h-4 w-4" />
              Event Progress
            </span>
            <span className="font-medium">{percentage.toFixed(1)}%</span>
          </div>
          <Progress value={percentage} className="h-3" />
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p className="text-muted-foreground">Completed</p>
              <p className="text-primary text-2xl font-bold">{formatDistance(coveredDistance)}</p>
              <p className="text-muted-foreground text-xs">miles</p>
            </div>
            <div>
              <p className="text-muted-foreground">Remaining</p>
              <p className="text-2xl font-bold">{formatDistance(remainingDistance)}</p>
              <p className="text-muted-foreground text-xs">miles</p>
            </div>
          </div>
        </div>

        {/* User Goal Progress (if set) */}
        {userGoal && goalPercentage !== null && (
          <div className="space-y-3 border-t pt-4">
            <div className="flex items-center justify-between text-sm">
              <span className="flex items-center gap-1">
                <TrendingUp className="text-muted-foreground h-4 w-4" />
                Personal Goal
              </span>
              <span className="font-medium">{goalPercentage.toFixed(1)}%</span>
            </div>
            <Progress value={goalPercentage} className="h-2" />
            <div className="text-muted-foreground flex justify-between text-sm">
              <span>{formatDistance(coveredDistance)} miles</span>
              <span>Goal: {formatDistance(userGoal)} miles</span>
            </div>
            {coveredDistance >= userGoal && (
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
              <p className="text-lg font-bold">{formatDistance(totalDistance)} miles</p>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
