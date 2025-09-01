import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { GaugeChart } from '@/components/ui/gauge-chart';
import { MapPin, Target, Image, ExternalLink } from 'lucide-react';
import { useMemo } from 'react';

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
}

export default function NextMilestone({
  milestone,
  userDistance,
  previousMilestoneDistance = 0,
  eventName,
  className = '',
}: NextMilestoneProps) {
  const { progress, distanceToGo, segmentProgress } = useMemo(() => {
    if (!milestone) {
      return { progress: 100, distanceToGo: 0, segmentProgress: 100 };
    }

    const distanceToGo = Math.max(milestone.distance - userDistance, 0);
    const progress = Math.min((userDistance / milestone.distance) * 100, 100);

    // Calculate progress within the current milestone segment
    const segmentStart = previousMilestoneDistance;
    const segmentTotal = milestone.distance - segmentStart;
    const segmentCovered = Math.max(userDistance - segmentStart, 0);
    const segmentProgress = segmentTotal > 0 ? Math.min((segmentCovered / segmentTotal) * 100, 100) : 100;

    return { progress, distanceToGo, segmentProgress };
  }, [milestone, userDistance, previousMilestoneDistance]);

  const formatDistance = (distance: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(distance);
  };

  if (!milestone) {
    return (
      <Card className={`${className}`}>
        <CardHeader>
          <CardTitle className="text-xl flex items-center gap-2">
            <Target className="h-5 w-5 text-primary" />
            Your Next Milestone
          </CardTitle>
          <CardDescription>Keep up the great work!</CardDescription>
        </CardHeader>
        <CardContent className="text-center py-8">
          <div className="mx-auto mb-4 h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
            <Target className="h-8 w-8 text-green-600 dark:text-green-400" />
          </div>
          <h3 className="text-lg font-semibold mb-2">All Milestones Completed!</h3>
          <p className="text-muted-foreground">
            Congratulations! You've reached all milestones in {eventName}.
          </p>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className={`${className}`}>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <CardTitle className="text-xl flex items-center gap-2">
            <MapPin className="h-5 w-5 text-primary" />
            Your Next Milestone
          </CardTitle>
        </div>
        <CardDescription>
          Next landmark on your {eventName} journey
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Milestone Info */}
        <div className="flex gap-4">
          <script>console.log('mile', milestone)</script>
          {milestone.logo && (
            <div className="flex-shrink-0">
              <img
                src={milestone.logo}
                alt={milestone.name}
                className="h-16 w-16 rounded-lg object-contain bg-muted p-2"
              />
            </div>
          )}
          <div className="flex-1 min-w-0">
            <h3 className="text-xl font-semibold mb-1">{milestone.name}</h3>
            <p className="text-sm text-muted-foreground mb-2">
              Mile {formatDistance(milestone.distance)} of {eventName}
            </p>
            {milestone.description && (
              <p className="text-sm text-muted-foreground">{milestone.description}</p>
            )}
          </div>
        </div>

        {/* Gauge Chart */}
        <div className="flex flex-col items-center py-4">
          <GaugeChart
            value={Math.max(userDistance - previousMilestoneDistance, 0)}
            max={milestone.distance - previousMilestoneDistance}
            label="miles to go"
            description={`Progress to ${milestone.name}`}
            color="hsl(var(--primary))"
            size={180}
            className="mb-2"
          />
          <div className="flex justify-between w-full text-xs text-muted-foreground mt-2">
            <span>Mile {formatDistance(previousMilestoneDistance)}</span>
            <span className="font-medium">{segmentProgress.toFixed(1)}% complete</span>
            <span>Mile {formatDistance(milestone.distance)}</span>
          </div>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-2 gap-4 pt-2">
          <div className="text-center p-3 bg-muted/50 rounded-lg">
            <p className="text-2xl font-bold text-primary">
              {formatDistance(userDistance)}
            </p>
            <p className="text-xs text-muted-foreground">Miles Completed</p>
          </div>
          <div className="text-center p-3 bg-muted/50 rounded-lg">
            <p className="text-2xl font-bold">
              {formatDistance(distanceToGo)}
            </p>
            <p className="text-xs text-muted-foreground">Miles Remaining</p>
          </div>
        </div>

        {/* Action Buttons */}
        {milestone.data && (
          <div className="flex gap-2 pt-2">
            {milestone.data.flyover_url && (
              <Button variant="outline" size="sm" className="flex-1" asChild>
                <a
                  href={milestone.data.flyover_url}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <ExternalLink className="h-4 w-4 mr-1" />
                  View Flyover
                </a>
              </Button>
            )}
            {milestone.data.landmark_image && (
              <Button variant="outline" size="sm" className="flex-1" asChild>
                <a
                  href={milestone.data.landmark_image}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <Image className="h-4 w-4 mr-1" />
                  View Image
                </a>
              </Button>
            )}
          </div>
        )}

        {/* Milestone Achievement Preview */}
        {distanceToGo < 10 && (
          <div className="p-3 bg-primary/5 border border-primary/20 rounded-lg">
            <p className="text-sm font-medium text-primary mb-1">
              🎉 Almost there!
            </p>
            <p className="text-xs text-muted-foreground">
              Just {formatDistance(distanceToGo)} miles until you reach {milestone.name}!
            </p>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
