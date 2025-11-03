import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartConfig, ChartContainer, ChartTooltip } from '@/components/ui/chart';
import { Activity, Bike, Clock, Footprints, MapPin } from 'lucide-react';
import { useMemo } from 'react';
import { Cell, Pie, PieChart, ResponsiveContainer } from 'recharts';

interface ActivityTypeData {
  name: string;
  miles: number;
  percentage: number;
  color: string;
}

interface MileageByActivityTypeProps {
  data?: ActivityTypeData[];
  totalMiles?: number;
  className?: string;
  dataFor?: string;
}

const activityIcons: Record<string, React.ComponentType<{ className?: string }>> = {
  Running: Footprints,
  Walking: MapPin,
  Cycling: Bike,
  Swimming: Activity,
  Other: Clock,
};

const defaultColors = [
  '#8884d8', // Purple
  '#82ca9d', // Green
  '#ffc658', // Yellow
  '#ff7c7c', // Red
  '#8dd1e1', // Light Blue
  '#d084d0', // Pink
  '#87d068', // Light Green
  '#ffb347', // Orange
];

const chartConfig = {
  miles: {
    label: 'Miles',
  },
} satisfies ChartConfig;

export default function MileageByActivityType({ data = [], totalMiles = 0, className = '', dataFor = 'you' }: MileageByActivityTypeProps) {
  const formatDistance = (distance: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(distance);
  };

  // Prepare chart data with colors
  const chartData = useMemo(() => {
    return data.map((item, index) => ({
      ...item,
      color: item.color || defaultColors[index % defaultColors.length],
    }));
  }, [data]);

  const hasData = chartData.length > 0 && totalMiles > 0;

  return (
    <Card className={`${className}`}>
      <CardHeader className="pb-4">
        <div className="flex items-center gap-2">
          <Activity className="text-primary h-6 w-6" />
          <CardTitle className="text-xl">Mileage by Activity Type</CardTitle>
        </div>
        <CardDescription>Breakdown of your miles by different activity types</CardDescription>
      </CardHeader>
      <CardContent>
        {!hasData ? (
          <div className="flex h-64 items-center justify-center text-center">
            <div>
              <Activity className="text-muted-foreground/50 mx-auto mb-4 h-12 w-12" />
              <p className="text-muted-foreground mb-2">No activity data available</p>
              <p className="text-muted-foreground/70 text-sm">Start logging activities to see your breakdown</p>
            </div>
          </div>
        ) : (
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Pie Chart */}
            <div className="flex items-center justify-center">
              <div className="w-full h-[350px]">
                <ChartContainer config={chartConfig}>
                  <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                      <Pie
                        data={chartData}
                        cx="50%"
                        cy="50%"
                        innerRadius={90}
                        outerRadius={140}
                        dataKey="miles"
                        stroke="hsl(var(--background))"
                        strokeWidth={2}
                      >
                        {chartData.map((entry, index) => (
                          <Cell key={`cell-${index}`} fill={entry.color} />
                        ))}
                      </Pie>
                      <ChartTooltip
                        content={({ active, payload }) => {
                          if (active && payload && payload.length > 0) {
                            const data = payload[0].payload;
                            return (
                              <div className="bg-background rounded-lg border p-3 shadow-lg">
                                <div className="mb-2 flex items-center gap-2">
                                  <div className="h-3 w-3 rounded-full" style={{ backgroundColor: data.color }} />
                                  <span className="font-medium">{data.name}</span>
                                </div>
                                <div className="space-y-1 text-sm">
                                  <div className="flex justify-between gap-4">
                                    <span>Miles:</span>
                                    <span className="font-medium">{formatDistance(data.miles)}</span>
                                  </div>
                                  <div className="flex justify-between gap-4">
                                    <span>Percentage:</span>
                                    <span className="font-medium">{data.percentage.toFixed(1)}%</span>
                                  </div>
                                </div>
                              </div>
                            );
                          }
                          return null;
                        }}
                      />
                    </PieChart>
                  </ResponsiveContainer>
                </ChartContainer>
              </div>
            </div>

            {/* Activity Breakdown */}
            <div className="flex flex-col justify-center">
              <h4 className="text-muted-foreground mb-4 text-sm font-medium tracking-wide uppercase">Activity Breakdown</h4>
              <div className="space-y-3">
                {chartData.map((item, index) => {
                  const Icon = activityIcons[item.name] || Activity;
                  return (
                    <div key={index} className="flex items-center justify-between gap-4 rounded-lg border bg-muted/30 p-3">
                      <div className="flex items-center gap-3 min-w-0">
                        <div className="flex-shrink-0 rounded-full p-2 text-white shadow-sm" style={{ backgroundColor: item.color }}>
                          <Icon className="h-4 w-4" />
                        </div>
                        <div className="min-w-0">
                          <div className="font-medium text-sm">{item.name}</div>
                          <div className="text-muted-foreground text-xs">{item.percentage.toFixed(1)}% of total</div>
                        </div>
                      </div>
                      <div className="flex-shrink-0 text-right">
                        <div className="text-foreground text-lg font-bold">{formatDistance(item.miles)}</div>
                        <div className="text-muted-foreground text-xs">miles</div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
