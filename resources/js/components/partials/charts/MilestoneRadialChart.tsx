'use client';

import { Label, PolarGrid, PolarRadiusAxis, RadialBar, RadialBarChart } from 'recharts';

import { ChartConfig, ChartContainer } from '@/components/ui/chart';

export const description = 'A radial chart with a custom shape';

type MilestoneRadialChartProps = {
  current: number;
  nextMilestone: number;
  previousMilestone: number;
};

const chartConfig = {
  miles: {
    label: 'Miles',
    color: 'var(--primary)',
  },
  progress: {
    label: 'Progress',
    color: 'var(--primary)',
  },
} satisfies ChartConfig;

export function MilestoneRadialChart({ current, nextMilestone, previousMilestone }: MilestoneRadialChartProps) {
  // Calculate miles remaining to next milestone
  const remaining = Math.max(0, nextMilestone - current);

  // Calculate progress between previous and next milestone
  const totalDistanceBetweenMilestones = nextMilestone - previousMilestone;
  const distanceFromPrevious = current - previousMilestone;
  const percentage = totalDistanceBetweenMilestones > 0
    ? Math.min((distanceFromPrevious / totalDistanceBetweenMilestones) * 100, 100)
    : 0;

  const chartData = [
    {
      name: 'progress',
      value: percentage,
      fill: percentage >= 100 ? 'hsl(var(--success))' : 'var(--color-progress)',
    },
  ];

  const startAngle = 90;
  const endAngle = startAngle - (360 * percentage) / 100;

  return (
    <ChartContainer config={chartConfig} className="mx-auto aspect-square max-h-[250px]">
      <RadialBarChart data={chartData} startAngle={startAngle} endAngle={endAngle} innerRadius={80} outerRadius={140}>
        <PolarGrid gridType="circle" radialLines={false} stroke="none" className="first:fill-muted last:fill-background" polarRadius={[86, 74]} />
        <RadialBar dataKey="value" background cornerRadius={10} />
        <PolarRadiusAxis tick={false} tickLine={false} axisLine={false}>
          <Label
            content={({ viewBox }) => {
              if (viewBox && 'cx' in viewBox && 'cy' in viewBox) {
                return (
                  <text x={viewBox.cx} y={viewBox.cy} textAnchor="middle" dominantBaseline="middle">
                    <tspan x={viewBox.cx} y={viewBox.cy} className="fill-foreground text-3xl font-bold">
                      {remaining.toFixed(2)}
                    </tspan>
                    <tspan x={viewBox.cx} y={(viewBox.cy || 0) + 24} className="fill-muted-foreground">
                      miles remaining
                    </tspan>
                  </text>
                );
              }
            }}
          />
        </PolarRadiusAxis>
      </RadialBarChart>
    </ChartContainer>
  );
}
