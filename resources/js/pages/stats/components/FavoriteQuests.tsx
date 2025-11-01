import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartConfig, ChartContainer, ChartTooltip } from '@/components/ui/chart';
import { Star } from 'lucide-react';
import { useMemo } from 'react';
import { Cell, Pie, PieChart, ResponsiveContainer } from 'recharts';

interface FavoriteQuestData {
  name: string;
  value: number;
  percentage: number;
}

interface FavoriteQuestsProps {
  data?: FavoriteQuestData[];
  totalRegistrations?: number;
  className?: string;
}

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
  registrations: {
    label: 'Registrations',
  },
} satisfies ChartConfig;

export default function FavoriteQuests({ data = [], totalRegistrations = 0, className = '' }: FavoriteQuestsProps) {
  const chartData = useMemo(() => {
    return data.map((item, index) => ({
      ...item,
      fill: defaultColors[index % defaultColors.length],
    }));
  }, [data]);

  const hasData = chartData.length > 0 && totalRegistrations > 0;

  return (
    <Card className={`${className}`}>
      <CardHeader className="pb-4">
        <div className="flex items-center gap-2">
          <Star className="text-primary h-6 w-6" />
          <CardTitle className="text-xl">Favorite Quests</CardTitle>
        </div>
        <CardDescription>Most scheduled quests this month</CardDescription>
      </CardHeader>
      <CardContent>
        {!hasData ? (
          <div className="flex h-80 items-center justify-center text-center">
            <div>
              <Star className="text-muted-foreground/50 mx-auto mb-4 h-12 w-12" />
              <p className="text-muted-foreground mb-2">No quest data available</p>
              <p className="text-muted-foreground/70 text-sm">Schedule quests this month to see your favorites</p>
            </div>
          </div>
        ) : (
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
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
                        innerRadius={60}
                        outerRadius={120}
                        dataKey="value"
                        stroke="hsl(var(--background))"
                        strokeWidth={2}
                      >
                        {chartData.map((entry, index) => (
                          <Cell key={`cell-${index}`} fill={entry.fill} />
                        ))}
                      </Pie>
                      <ChartTooltip
                        content={({ active, payload }) => {
                          if (active && payload && payload.length > 0) {
                            const data = payload[0].payload;
                            return (
                              <div className="bg-background rounded-lg border p-3 shadow-lg">
                                <div className="mb-2 flex items-center gap-2">
                                  <div className="h-3 w-3 rounded-full" style={{ backgroundColor: data.fill }} />
                                  <span className="font-medium">{data.name}</span>
                                </div>
                                <div className="space-y-1 text-sm">
                                  <div className="flex justify-between gap-4">
                                    <span>Registrations:</span>
                                    <span className="font-medium">{data.value}</span>
                                  </div>
                                  <div className="flex justify-between gap-4">
                                    <span>Percentage:</span>
                                    <span className="font-medium">{data.percentage}%</span>
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

            {/* Quest List */}
            <div className="flex flex-col justify-center">
              <div className="space-y-3">
                {chartData.map((item, index) => (
                  <div key={index} className="flex items-center justify-between gap-4 rounded-lg border bg-muted/30 p-3">
                    <div className="flex items-center gap-3 min-w-0 flex-1">
                      <div className="flex-shrink-0 h-4 w-4 rounded-full" style={{ backgroundColor: item.fill }} />
                      <div className="min-w-0 flex-1">
                        <div className="font-medium text-sm truncate">{item.name}</div>
                      </div>
                    </div>
                    <div className="flex-shrink-0 text-right">
                      <div className="text-foreground text-lg font-bold">{item.value}</div>
                      <div className="text-muted-foreground text-xs">{item.percentage}%</div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
