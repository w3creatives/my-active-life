import { MilestoneRadialChart } from '@/components/partials/charts/MilestoneRadialChart';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export default function ProgressCard() {
  const { auth } = usePage<SharedData>().props;

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-2xl">Your Progress So Far</CardTitle>
        <CardDescription></CardDescription>
      </CardHeader>
      <CardContent className="space-y-2">
        <MilestoneRadialChart current={240} milestone={300} />
        <div className="text-muted-foreground">
          <p>
            You have CRUSHED {auth.user.id} miles of {auth.total_points.name}! You have {auth.user.id} miles to go.
          </p>
        </div>
      </CardContent>
    </Card>
  );
}
