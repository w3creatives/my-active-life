'use client';

import { Label, PolarGrid, PolarRadiusAxis, RadialBar, RadialBarChart } from 'recharts';

import { ChartConfig, ChartContainer } from '@/components/ui/chart';

export const description = 'A radial chart with a custom shape';

type MilestoneRadialChartProps = {
  current: number;
  milestone: number;
};

const chartConfig = {
  miles: {
    label: 'Miles',
  },
  safari: {
    label: 'Safari',
    color: 'var(--color-primary)',
  },
} satisfies ChartConfig;

export function MilestoneRadialChart({ current, milestone }: MilestoneRadialChartProps) {
  const remaining = milestone - current;
  const percentage = Math.min((current / milestone) * 100, 100);

  const chartData = [
    {
      name: 'progress',
      value: percentage,
      fill: '#4F46E5', // customize this color as needed
    },
  ];

  const startAngle = 90;
  const endAngle = startAngle - (360 * percentage) / 100;

  return (
    <ChartContainer config={chartConfig} className="mx-auto aspect-square max-h-[250px]">
      <RadialBarChart data={chartData} startAngle={startAngle} endAngle={endAngle} innerRadius={80} outerRadius={140}>
        <PolarGrid gridType="circle" radialLines={false} stroke="none" className="first:fill-muted last:fill-background" polarRadius={[86, 74]} />
        <RadialBar dataKey="visitors" background />
        <PolarRadiusAxis tick={false} tickLine={false} axisLine={false}>
          <Label
            content={({ viewBox }) => {
              if (viewBox && 'cx' in viewBox && 'cy' in viewBox) {
                return (
                  <text x={viewBox.cx} y={viewBox.cy} textAnchor="middle" dominantBaseline="middle">
                    <tspan x={viewBox.cx} y={viewBox.cy} className="fill-foreground text-4xl font-bold">
                      {remaining}
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
