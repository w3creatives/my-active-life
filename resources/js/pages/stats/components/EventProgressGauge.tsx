import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import { Trophy, Target, TrendingUp } from 'lucide-react';
import { useMemo } from 'react';

interface EventProgressGaugeProps {
  eventName: string;
  totalDistance: number;
  coveredDistance: number;
  userGoal?: number;
  className?: string;
}

export default function EventProgressGauge({
  eventName,
  totalDistance,
  coveredDistance,
  userGoal,
  className = '',
}: EventProgressGaugeProps) {
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
          <CardTitle className="text-xl flex items-center gap-2">
            <Trophy className="h-5 w-5 text-primary" />
            {eventName} Progress
          </CardTitle>
          {isCompleted && (
            <Badge variant="default" className="bg-green-500">
              <Trophy className="h-3 w-3 mr-1" />
              Complete!
            </Badge>
          )}
        </div>
        <CardDescription>
          Your journey through {eventName}
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Main Progress */}
        <div className="space-y-3">
          <div className="flex items-center justify-between text-sm">
            <span className="flex items-center gap-1">
              <Target className="h-4 w-4 text-muted-foreground" />
              Event Progress
            </span>
            <span className="font-medium">{percentage.toFixed(1)}%</span>
          </div>
          <Progress value={percentage} className="h-3" />
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p className="text-muted-foreground">Completed</p>
              <p className="text-2xl font-bold text-primary">
                {formatDistance(coveredDistance)}
              </p>
              <p className="text-xs text-muted-foreground">miles</p>
            </div>
            <div>
              <p className="text-muted-foreground">Remaining</p>
              <p className="text-2xl font-bold">
                {formatDistance(remainingDistance)}
              </p>
              <p className="text-xs text-muted-foreground">miles</p>
            </div>
          </div>
        </div>

        {/* User Goal Progress (if set) */}
        {userGoal && goalPercentage !== null && (
          <div className="space-y-3 pt-4 border-t">
            <div className="flex items-center justify-between text-sm">
              <span className="flex items-center gap-1">
                <TrendingUp className="h-4 w-4 text-muted-foreground" />
                Personal Goal
              </span>
              <span className="font-medium">{goalPercentage.toFixed(1)}%</span>
            </div>
            <Progress value={goalPercentage} className="h-2" />
            <div className="flex justify-between text-sm text-muted-foreground">
              <span>{formatDistance(coveredDistance)} miles</span>
              <span>Goal: {formatDistance(userGoal)} miles</span>
            </div>
            {coveredDistance >= userGoal && (
              <Badge variant="default" className="bg-green-500 w-fit">
                <Trophy className="h-3 w-3 mr-1" />
                Goal Achieved!
              </Badge>
            )}
          </div>
        )}

        {/* Event Total Information */}
        <div className="pt-4 border-t">
          <div className="grid grid-cols-1 text-center">
            <div>
              <p className="text-xs text-muted-foreground uppercase tracking-wide">
                Event Total Distance
              </p>
              <p className="text-lg font-bold">
                {formatDistance(totalDistance)} miles
              </p>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}