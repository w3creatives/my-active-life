import { MilestoneRadialChart } from '@/components/partials/charts/MilestoneRadialChart';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function MilesToNextBib() {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-xl">Miles to Next Bib</CardTitle>
      </CardHeader>
      <CardContent>
        <MilestoneRadialChart current={240} milestone={300} />
      </CardContent>
    </Card>
  );
}
