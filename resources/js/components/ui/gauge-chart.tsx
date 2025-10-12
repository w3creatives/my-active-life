'use client';

import { Label, PolarGrid, PolarRadiusAxis, RadialBar, RadialBarChart } from 'recharts';
import { ChartConfig, ChartContainer } from '@/components/ui/chart';

interface GaugeChartProps {
  value: number;
  max: number;
  label?: string;
  description?: string;
  color?: string;
  size?: number;
  className?: string;
  showPercentage?: boolean;
}

const chartConfig = {
  progress: {
    label: 'Progress',
  },
} satisfies ChartConfig;

export function GaugeChart({
  value,
  max,
  label,
  description,
  color = 'hsl(var(--primary))',
  size = 180,
  className = '',
  showPercentage = false,
}: GaugeChartProps) {
  // Safety checks for invalid data
  const safeValue = Math.max(0, value || 0);
  const safeMax = Math.max(1, max || 1);

  const percentage = Math.min((safeValue / safeMax) * 100, 100);
  const remaining = Math.max(safeMax - safeValue, 0);

  // Create chart data for semi-circle gauge
  const chartData = [
    {
      name: 'gauge',
      value: percentage,
      fill: color,
    },
  ];

  const formatValue = (val: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(val);
  };

  return (
    <div className={`flex flex-col items-center ${className}`}>
      <ChartContainer
        config={chartConfig}
        className="mx-auto aspect-square"
        style={{ maxHeight: `${size}px`, maxWidth: `${size}px`, height: `${size}px`, width: `${size}px` }}
      >
        <RadialBarChart
          data={chartData}
          cx="50%"
          cy="50%"
          startAngle={180}
          endAngle={0}
          innerRadius={60}
          outerRadius={80}
        >
          <PolarGrid
            gridType="circle"
            radialLines={false}
            stroke="none"
            className="first:fill-muted last:fill-background"
            polarRadius={[86, 74]}
          />
          <RadialBar
            dataKey="value"
            cornerRadius={10}
            fill={color}
            background={{ fill: 'hsl(var(--muted))' }}
            className="stroke-2"
          />
          <PolarRadiusAxis
            tick={false}
            tickLine={false}
            axisLine={false}
          >
            <Label
              content={({ viewBox }) => {
                if (viewBox && 'cx' in viewBox && 'cy' in viewBox) {
                  return (
                    <text
                      x={viewBox.cx}
                      y={viewBox.cy}
                      textAnchor="middle"
                      dominantBaseline="middle"
                    >
                      <tspan
                        x={viewBox.cx}
                        y={viewBox.cy}
                        className="fill-foreground text-3xl font-bold"
                      >
                        {showPercentage ? `${Math.round(percentage)}%` : formatValue(remaining)}
                      </tspan>
                      <tspan
                        x={viewBox.cx}
                        y={(viewBox.cy || 0) + 24}
                        className="fill-muted-foreground text-sm"
                      >
                        {showPercentage ? 'complete' : (label || 'remaining')}
                      </tspan>
                    </text>
                  );
                }
              }}
            />
          </PolarRadiusAxis>
        </RadialBarChart>
      </ChartContainer>

      {description && (
        <p className="mt-2 text-center text-sm text-muted-foreground">
          {description}
        </p>
      )}

      {/* Progress indicators */}
      <div className="mt-2 flex justify-between w-full text-xs text-muted-foreground">
        <span>0</span>
        <span>{formatValue(safeMax)}</span>
      </div>
    </div>
  );
}
