import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { Skeleton } from '@/components/ui/skeleton';
import { Badge } from '@/components/ui/badge';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState, useMemo } from 'react';
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, XAxis, YAxis } from 'recharts';
import { Calendar } from 'lucide-react';

type MonthlyPointsData = {
  label: string;
  amount: number;
};

export const description = 'A bar chart';

const chartConfig = {
  amount: {
    label: 'Miles',
    color: 'var(--color-primary)',
  },
} satisfies ChartConfig;

export default function MonthlyPoints() {
  const { auth } = usePage<SharedData>().props;
  const [loading, setLoading] = useState(true);
  const [monthlies, setMonthlies] = useState<MonthlyPointsData[]>([]);

  useEffect(() => {
    const fetchMonthlies = async () => {
      try {
        const response = await axios.get(route('userstats', ['monthlies']), {
          params: {
            event_id: auth.preferred_event.id,
            user_id: auth.user.id,
          },
        });
        setMonthlies(response.data);
        setLoading(false);
      } catch (err) {
        console.error('Error fetching monthlies:', err);
        setLoading(false);
      }
    };

    fetchMonthlies();
  }, []);

  const statsData = useMemo(() => {
    if (monthlies.length === 0) {
      return {
        totalMiles: 0,
        averageMiles: 0,
        bestMonth: null,
        monthlyData: []
      };
    }

    const monthlyData = monthlies.map((item: MonthlyPointsData) => ({
      ...item,
      amount: parseFloat(item.amount.toString()),
    }));

    const totalMiles = monthlyData.reduce((sum, item) => sum + item.amount, 0);
    const averageMiles = totalMiles / monthlyData.length;
    const bestMonth = monthlyData.reduce((best, current) =>
      current.amount > (best?.amount || 0) ? current : best
    );

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
        <Card>
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
              <Calendar className="h-5 w-5 text-primary" />
              <CardTitle className="text-lg">Monthly Mileage</CardTitle>
            </div>
            <CardDescription className="text-sm">
              Your month-over-month progress throughout the event
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {/* Stats Summary */}
            <div className="grid grid-cols-3 gap-3">
              <div className="text-center p-3 rounded-lg bg-primary/5 border">
                <div className="text-xl font-bold text-primary">
                  {formatDistance(statsData.totalMiles)}
                </div>
                <div className="text-xs text-muted-foreground">Total Miles</div>
              </div>
              <div className="text-center p-3 rounded-lg bg-muted/30 border">
                <div className="text-xl font-bold">
                  {formatDistance(statsData.averageMiles)}
                </div>
                <div className="text-xs text-muted-foreground">Avg per Month</div>
              </div>
              <div className="text-center p-3 rounded-lg bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800">
                <div className="text-xl font-bold text-green-600 dark:text-green-400">
                  {statsData.bestMonth ? formatDistance(statsData.bestMonth.amount) : '0'}
                </div>
                <div className="flex text-xs text-muted-foreground items-center justify-center gap-1">
                  <span>Best Month</span>
                  {statsData.bestMonth && (
                      <>
                        - <span className='font-bold'>{statsData.bestMonth.label}</span>
                      </>
                  )}
                </div>
              </div>
            </div>

            {/* Chart */}
            <div className="h-64">
              <ChartContainer config={chartConfig}>
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={statsData.monthlyData} margin={{ top: 20, right: 20, left: 10, bottom: 5 }}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis
                      dataKey="label"
                      tickLine={false}
                      axisLine={false}
                      tickMargin={10}
                      tickFormatter={(value) => {
                        // Show first 3 letters of month
                        return value.slice(0, 3);
                      }}
                    />
                    <YAxis
                      tickLine={false}
                      axisLine={false}
                      tickMargin={10}
                      label={{ value: 'Miles', angle: -90, position: 'insideLeft' }}
                      domain={[0, 'auto']}
                    />
                    <ChartTooltip
                      content={({ active, payload, label }) => {
                        if (active && payload && payload.length) {
                          const data = payload[0];
                          return (
                            <div className="rounded-lg border bg-background p-3 shadow-lg">
                              <div className="font-medium mb-2">{label}</div>
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
                      radius={[4, 4, 0, 0]}
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
