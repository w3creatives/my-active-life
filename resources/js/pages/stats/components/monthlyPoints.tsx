import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartConfig, ChartContainer, ChartTooltip } from '@/components/ui/chart';
import { Skeleton } from '@/components/ui/skeleton';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { Calendar } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, XAxis, YAxis } from 'recharts';

type MonthlyPointsData = {
    label: string;
    amount: number;
};
type PointStatData = {
    current_day: object;
    current_week: object;
    current_month:object;
};

export const description = 'A bar chart';

const chartConfig = {
  amount: {
    label: 'Miles',
    color: 'var(--color-primary)',
  },
} satisfies ChartConfig;

interface MonthlyPointsProps {
  dataFor: string;
}

export default function MonthlyPoints({ dataFor }: MonthlyPointsProps) {
  const { auth } = usePage<SharedData>().props;
  const [loading, setLoading] = useState(true);
    const [monthlies, setMonthlies] = useState<MonthlyPointsData[]>([]);
    const [pointStat, setPointStat] = useState<PointStatData[]>([]);

  useEffect(() => {
    const fetchMonthlies = async () => {
      setLoading(true); // Set loading to true when dataFor changes
      try {
        const routeName = dataFor === 'team' ? 'teamstats' : 'userstats';
        const response = await axios.get(route(routeName, ['monthlies']), {
          params: {
            event_id: auth.preferred_event.id,
            user_id: auth.user.id,
          },
        });
          setMonthlies(response.data.data);
          setPointStat(response.data.pointStat);
        setLoading(false);
      } catch (err) {
        console.error('Error fetching monthlies:', err);
        setLoading(false);
      }
    };

    fetchMonthlies();
  }, [dataFor]);

  const statsData = useMemo(() => {
    if (monthlies.length === 0) {
      return {
        totalMiles: 0,
        averageMiles: 0,
        bestMonth: null,
        monthlyData: [],
      };
    }

    // Sort data by month to ensure proper order (Jan, Feb, Mar, etc.)
    const monthOrder = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    const monthlyData = monthlies
      .map((item: MonthlyPointsData) => ({
        ...item,
        amount: parseFloat(item.amount.toString()),
      }))
      .sort((a, b) => {
        const aIndex = monthOrder.indexOf(a.label);
        const bIndex = monthOrder.indexOf(b.label);
        return aIndex - bIndex;
      });

    const totalMiles = monthlyData.reduce((sum, item) => sum + item.amount, 0);
    const averageMiles = monthlyData.length > 0 ? totalMiles / monthlyData.length : 0;
    const bestMonth = monthlyData.length > 0
      ? monthlyData.reduce((best, current) => (current.amount > (best?.amount || 0) ? current : best))
      : null;

    return { totalMiles, averageMiles, bestMonth, monthlyData };
  }, [monthlies]);

  const formatDistance = (distance: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 1,
    }).format(distance);
  };

  return (
    <>
      {loading ? (
        <Card className="grid grid-cols-1">
          <CardHeader>
            <Skeleton className="mb-2 h-6 w-full" />
            <Skeleton className="h-4 w-full" />
          </CardHeader>
          <CardContent>
            <div className="flex h-60 w-full items-end gap-2 px-4">
              {[40, 60, 30, 50, 70, 20, 60, 80, 55, 45, 35, 65].map((height, idx) => (
                <Skeleton key={idx} className="w-18 rounded-md" style={{ height: `${height}%` }} />
              ))}
            </div>
          </CardContent>
          <CardFooter className="flex-col items-start gap-2 px-4 pb-4 text-sm">
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-4 w-full" />
          </CardFooter>
        </Card>
      ) : (
        <Card>
          <CardHeader className="pb-3">
            <div className="flex items-center gap-2">
              <Calendar className="text-primary h-5 w-5" />
              <CardTitle className="text-lg">Monthly Mileage</CardTitle>
            </div>
            <CardDescription className="text-sm">{dataFor === 'team' ? 'Team' : 'Your'} month-over-month progress for {new Date().getFullYear()}</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {/* Stats Summary */}
            <div className="grid grid-cols-4 gap-3">
                <div className="bg-muted/30 rounded-lg border p-3 text-center">
                    <div className="text-xl font-bold">{formatDistance(statsData.averageMiles)}</div>
                    <div className="text-muted-foreground text-xs">Avg per Month</div>
                </div>
                <div className="bg-primary/5 rounded-lg border p-3 text-center">
                    <div className="text-primary text-xl font-bold">{formatDistance(pointStat.current_day.accomplishment)} miles</div>
                    <div className="text-muted-foreground text-xs">Today</div>
                </div>
                <div className="bg-primary/5 rounded-lg border p-3 text-center">
                    <div className="text-primary text-xl font-bold">{formatDistance(pointStat.current_week.accomplishment)} miles</div>
                    <div className="text-muted-foreground text-xs">This Week</div>
                </div>
                <div className="bg-primary/5 rounded-lg border p-3 text-center">
                    <div className="text-primary text-xl font-bold">{formatDistance(pointStat.current_month.accomplishment)} miles</div>
                    <div className="text-muted-foreground text-xs">This Month</div>
                </div>

            </div>

            {/* Chart */}
            <div className="h-64 w-full overflow-hidden">
              <ChartContainer config={chartConfig} className="h-full w-full">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart
                    data={statsData.monthlyData}
                    margin={{ top: 10, right: 10, left: 10, bottom: 5 }}
                  >
                    <CartesianGrid strokeDasharray="3 3" stroke="hsl(var(--muted))" strokeOpacity={0.3} />
                    <XAxis
                      dataKey="label"
                      tickLine={false}
                      axisLine={false}
                      tickMargin={8}
                      fontSize={12}
                      tick={{ fill: 'hsl(var(--muted-foreground))' }}
                    />
                    <YAxis
                      tickLine={false}
                      axisLine={false}
                      tickMargin={8}
                      fontSize={11}
                      tick={{ fill: 'hsl(var(--muted-foreground))' }}
                      domain={[0, 'dataMax']}
                      allowDecimals={false}
                    />
                    <ChartTooltip
                      content={({ active, payload, label }) => {
                        if (active && payload && payload.length) {
                          const data = payload[0];
                          return (
                            <div className="bg-background rounded-lg border p-3 shadow-lg">
                              <div className="mb-2 font-medium">{label} {new Date().getFullYear()}</div>
                              <div className="text-sm">
                                <span className="text-muted-foreground">Miles: </span>
                                <span className="font-medium">{formatDistance(data.value as number)}</span>
                              </div>
                            </div>
                          );
                        }
                        return null;
                      }}
                    />
                    <Bar
                      dataKey="amount"
                      fill="var(--color-primary)"
                      radius={[6, 6, 0, 0]}
                      maxBarSize={50}
                    />
                  </BarChart>
                </ResponsiveContainer>
              </ChartContainer>
            </div>
          </CardContent>
        </Card>
      )}
    </>
  );
}
