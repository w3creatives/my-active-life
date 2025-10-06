import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Trophy } from 'lucide-react';
import { useMemo } from 'react';

interface NextMilestone {
  id: number;
  name: string;
  distance: number;
  description?: string;
  logo_image_url?: string;
  team_logo_image_url?: string;
}

interface NextMilestoneCardProps {
  nextMilestone: NextMilestone | null;
  currentDistance: number;
  previousMilestone?: NextMilestone | null;
  eventName: string;
  showTeamView?: boolean;
  className?: string;
}

export default function NextMilestoneCard({
  nextMilestone,
  currentDistance,
  previousMilestone,
  eventName,
  showTeamView = false,
  className = ""
}: NextMilestoneCardProps) {
  const getTitle = (eventName: string) => {
    if (eventName.includes("Run The Year")) {
      return "Your Next Bib";
    } else if (eventName.includes("Amerithon")) {
      return "Your Next Landmark";
    } else if (eventName.includes("5K") || eventName.includes("Challenge")) {
      return "Your Next Badge";
    } else if (eventName.includes("JOGLE")) {
      return "Your Next Milestone";
    } else {
      return "Your Next Milestone";
    }
  };

  const progressData = useMemo(() => {
    if (!nextMilestone || !previousMilestone) {
      return {
        distanceToNext: nextMilestone ? nextMilestone.distance - currentDistance : 0,
        distanceFromPrevious: currentDistance - (previousMilestone?.distance || 0),
        totalDistance: nextMilestone ? nextMilestone.distance - (previousMilestone?.distance || 0) : 100,
        progressPercentage: 0
      };
    }

    const distanceToNext = nextMilestone.distance - currentDistance;
    const distanceFromPrevious = currentDistance - previousMilestone.distance;
    const totalDistance = nextMilestone.distance - previousMilestone.distance;
    const progressPercentage = totalDistance > 0 ? (distanceFromPrevious / totalDistance) * 100 : 0;

    return {
      distanceToNext: Math.max(0, distanceToNext),
      distanceFromPrevious,
      totalDistance,
      progressPercentage: Math.min(100, Math.max(0, progressPercentage))
    };
  }, [nextMilestone, currentDistance, previousMilestone]);

  const formatDistance = (distance: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 1,
    }).format(distance);
  };

  if (!nextMilestone) {
    return (
      <Card className={`${className}`}>
        <CardHeader className="text-center">
          <CardTitle className="flex items-center justify-center gap-2 text-2xl">
            <Trophy className="size-5 text-amber-500" />
            {getTitle(eventName)}
          </CardTitle>
        </CardHeader>
        <CardContent className="text-center py-8">
          <div className="text-muted-foreground">
            <Trophy className="h-16 w-16 mx-auto mb-4 opacity-50" />
            <p>Congratulations! You've completed all milestones!</p>
          </div>
        </CardContent>
      </Card>
    );
  }

  const imageUrl = nextMilestone.logo_image_url;

  return (
    <Card className={`${className}`}>
      <CardHeader className="text-center">
        <CardTitle className="flex items-center justify-center gap-2 text-xl">
          <Trophy className="size-5 text-amber-500" />
          {getTitle(eventName)}
        </CardTitle>
      </CardHeader>

      <CardContent className="space-y-4">
        {/* Milestone Image */}
        <div className="flex justify-center">
          <div className="relative">
            <div className="size-60 rounded-lg bg-muted/20 flex items-center justify-center overflow-hidden">
              {imageUrl ? (
                <img
                  src={imageUrl}
                  alt={nextMilestone.name}
                  className="w-full h-full object-contain"
                />
              ) : (
                <Trophy className="h-12 w-12 text-muted-foreground/50" />
              )}
            </div>
          </div>
        </div>

        {/* Milestone Name */}
        <div className="text-center">
          <h2 className="font-semibold text-2xl">{nextMilestone.name}</h2>
          {nextMilestone.description && (
            <p className="text-sm text-muted-foreground mt-1">
              {nextMilestone.description}
            </p>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
