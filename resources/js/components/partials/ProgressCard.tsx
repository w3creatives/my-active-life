import { LottieGoal } from '@/components/partials/lottie/LottieGoal';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import VisualProgressMap from '@/components/partials/VisualProgressMap';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import AmerithonMap from '../atoms/AmerithonMap';

interface ProgressCardProps {
  totalPoints: number;
  goal: number;
  title?: string;
}

export default function ProgressCard({ totalPoints, goal, title }: ProgressCardProps) {
  const { auth } = usePage<SharedData>().props;
  const eventName = auth?.preferred_event?.name || '';

  // Determine if this is an Amerithon event
  const isAmerithonEvent = eventName.toLowerCase().includes('amerithon');

  if (isAmerithonEvent) {
    // For Amerithon events, use the dashboard-specific visual progress map
    return <>
      <AmerithonProgressCard totalPoints={totalPoints} goal={goal} title={title} />
    </>;
  }

  // For RTY and other events, use the Lottie animation
  return (
    <RTYProgressCard totalPoints={totalPoints} goal={goal} title={title} />
  );
}

const RTYProgressCard = ({ totalPoints, goal, title }: ProgressCardProps) => {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-xl">{title ? title : 'Your Progress'}</CardTitle>
      </CardHeader>
      <CardContent className="px-0">
        <LottieGoal currentPoints={totalPoints} goal={goal} />
        <div className="flex justify-center">
          <div className="border-primary rounded border px-6 py-2 text-center">
            <h3 className="text-2xl font-semibold">
              {totalPoints.toFixed(2)}
              <small className="text-xs font-normal">miles</small>
            </h3>
            <h4 className="text-xs">
              of <span className="text-base">{goal}</span> miles
            </h4>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

const AmerithonProgressCard = ({ totalPoints, goal, title }: ProgressCardProps) => {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-xl">{title ? title : 'Your Progress'}</CardTitle>
      </CardHeader>
      <CardContent className="px-0">
        <LottieGoal currentPoints={totalPoints} goal={goal} />
        <div className="flex justify-center">
          <div className="border-primary rounded border px-6 py-2 text-center">
            <h3 className="text-2xl font-semibold">
              {totalPoints.toFixed(2)}
              <small className="text-xs font-normal">miles</small>
            </h3>
            <h4 className="text-xs">
              of <span className="text-base">{goal}</span> miles
            </h4>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
