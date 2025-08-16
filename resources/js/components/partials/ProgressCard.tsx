import { LottieGoal } from '@/components/partials/lottie/LottieGoal';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface ProgressCardProps {
  totalPoints: number;
  goal: number;
  title?: string;
}

export default function ProgressCard({ totalPoints, goal, title }: ProgressCardProps) {
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
              {totalPoints}
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
