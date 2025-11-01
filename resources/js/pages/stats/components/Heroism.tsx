import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartConfig, ChartContainer } from '@/components/ui/chart';
import { Trophy } from 'lucide-react';
import { useMemo } from 'react';
import { Cell, Label, Pie, PieChart, ResponsiveContainer } from 'recharts';

interface HeroismData {
  total_registrations: number;
  total_completed: number;
  completion_percentage: number;
}

interface HeroismProps {
  lifetime?: HeroismData;
  last30Days?: HeroismData;
  className?: string;
}

const chartConfig = {
  completion: {
    label: 'Completion Rate',
  },
} satisfies ChartConfig;

// Color function based on percentage
const getColor = (percentage: number) => {
  if (percentage >= 90) return '#55BF3B'; // Green
  if (percentage >= 50) return '#DDDF0D'; // Yellow
  return '#DF5353'; // Red
};

export default function Heroism({ lifetime, last30Days, className = '' }: HeroismProps) {
  const lifetimeData = useMemo(() => {
    if (!lifetime) return [];
    return [
      { name: 'Completed', value: lifetime.completion_percentage, fill: getColor(lifetime.completion_percentage) },
      { name: 'Remaining', value: 100 - lifetime.completion_percentage, fill: '#e5e7eb' },
    ];
  }, [lifetime]);

  const last30DaysData = useMemo(() => {
    if (!last30Days) return [];
    return [
      { name: 'Completed', value: last30Days.completion_percentage, fill: getColor(last30Days.completion_percentage) },
      { name: 'Remaining', value: 100 - last30Days.completion_percentage, fill: '#e5e7eb' },
    ];
  }, [last30Days]);

  const hasLifetimeData = lifetime && lifetime.total_registrations > 0;
  const hasLast30DaysData = last30Days && last30Days.total_registrations > 0;

  return (
    <>
      {/* Lifetime Heroism */}
      <Card className={`${className}`}>
        <CardHeader className="pb-4">
          <div className="flex items-center gap-2">
            <Trophy className="text-primary h-6 w-6" />
            <CardTitle className="text-xl">Heroism</CardTitle>
          </div>
          <CardDescription className="text-base font-medium">Lifetime</CardDescription>
        </CardHeader>
        <CardContent>
          {!hasLifetimeData ? (
            <div className="flex h-64 items-center justify-center text-center">
              <div>
                <Trophy className="text-muted-foreground/50 mx-auto mb-4 h-12 w-12" />
                <p className="text-muted-foreground mb-2">No quest data available</p>
                <p className="text-muted-foreground/70 text-sm">Start completing quests to see your heroism</p>
              </div>
            </div>
          ) : (
            <div className="flex flex-col items-center gap-4">
              <div className="w-full h-[250px]">
                <ChartContainer config={chartConfig}>
                  <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                      <Pie
                        data={lifetimeData}
                        cx="50%"
                        cy="50%"
                        startAngle={180}
                        endAngle={0}
                        innerRadius={60}
                        outerRadius={100}
                        dataKey="value"
                        stroke="none"
                      >
                        {lifetimeData.map((entry, index) => (
                          <Cell key={`cell-${index}`} fill={entry.fill} />
                        ))}
                        <Label
                          content={({ viewBox }) => {
                            if (viewBox && 'cx' in viewBox && 'cy' in viewBox) {
                              return (
                                <text x={viewBox.cx} y={viewBox.cy} textAnchor="middle">
                                  <tspan
                                    x={viewBox.cx}
                                    y={(viewBox.cy || 0) - 10}
                                    className="fill-foreground text-4xl font-bold"
                                  >
                                    {lifetime.completion_percentage}
                                  </tspan>
                                  <tspan
                                    x={viewBox.cx}
                                    y={(viewBox.cy || 0) + 20}
                                    className="fill-muted-foreground text-sm"
                                  >
                                    %
                                  </tspan>
                                </text>
                              );
                            }
                          }}
                        />
                      </Pie>
                    </PieChart>
                  </ResponsiveContainer>
                </ChartContainer>
              </div>
              <div className="text-center">
                <p className="text-muted-foreground text-sm">
                  You completed <span className="font-bold text-foreground">{lifetime.total_completed}</span> Quest(s) out
                  of <span className="font-bold text-foreground">{lifetime.total_registrations}</span> you scheduled.
                </p>
              </div>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Last 30 Days Heroism */}
      <Card className={`${className}`}>
        <CardHeader className="pb-4">
          <div className="flex items-center gap-2">
            <Trophy className="text-primary h-6 w-6" />
            <CardTitle className="text-xl">Heroism</CardTitle>
          </div>
          <CardDescription className="text-base font-medium">Last 30 Days</CardDescription>
        </CardHeader>
        <CardContent>
          {!hasLast30DaysData ? (
            <div className="flex h-64 items-center justify-center text-center">
              <div>
                <Trophy className="text-muted-foreground/50 mx-auto mb-4 h-12 w-12" />
                <p className="text-muted-foreground mb-2">No quest data available</p>
                <p className="text-muted-foreground/70 text-sm">Start completing quests to see your heroism</p>
              </div>
            </div>
          ) : (
            <div className="flex flex-col items-center gap-4">
              <div className="w-full h-[250px]">
                <ChartContainer config={chartConfig}>
                  <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                      <Pie
                        data={last30DaysData}
                        cx="50%"
                        cy="50%"
                        startAngle={180}
                        endAngle={0}
                        innerRadius={60}
                        outerRadius={100}
                        dataKey="value"
                        stroke="none"
                      >
                        {last30DaysData.map((entry, index) => (
                          <Cell key={`cell-${index}`} fill={entry.fill} />
                        ))}
                        <Label
                          content={({ viewBox }) => {
                            if (viewBox && 'cx' in viewBox && 'cy' in viewBox) {
                              return (
                                <text x={viewBox.cx} y={viewBox.cy} textAnchor="middle">
                                  <tspan
                                    x={viewBox.cx}
                                    y={(viewBox.cy || 0) - 10}
                                    className="fill-foreground text-4xl font-bold"
                                  >
                                    {last30Days.completion_percentage}
                                  </tspan>
                                  <tspan
                                    x={viewBox.cx}
                                    y={(viewBox.cy || 0) + 20}
                                    className="fill-muted-foreground text-sm"
                                  >
                                    %
                                  </tspan>
                                </text>
                              );
                            }
                          }}
                        />
                      </Pie>
                    </PieChart>
                  </ResponsiveContainer>
                </ChartContainer>
              </div>
              <div className="text-center">
                <p className="text-muted-foreground text-sm">
                  You completed <span className="font-bold text-foreground">{last30Days.total_completed}</span> Quest(s)
                  out of <span className="font-bold text-foreground">{last30Days.total_registrations}</span> you scheduled.
                </p>
              </div>
            </div>
          )}
        </CardContent>
      </Card>
    </>
  );
}
