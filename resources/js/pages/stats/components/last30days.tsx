import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { Skeleton } from '@/components/ui/skeleton';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useMemo, useState } from 'react';
import { Bar, CartesianGrid, ComposedChart, Legend, Line, XAxis, YAxis } from 'recharts';

export const description = 'A bar chart';

const chartConfig = {
  amount: {
    label: 'Miles',
    color: 'var(--color-primary)',
  },
  average: {
    label: '7-Day Avg',
    color: 'var(--color-secondary, #f7a35c)',
  },
} satisfies ChartConfig;

type Last30DaysData = {
  label: string;
  daily_total: number;
  seven_day_avg?: number;
};

interface Last30daysProps {
  dataFor: string;
}

export default function Last30days({ dataFor }: Last30daysProps) {
  const { auth } = usePage<SharedData>().props;
  const [loading, setLoading] = useState(true);
  const [last30days, setLast30days] = useState<Last30DaysData[]>([]);

  useEffect(() => {
    const fetchMonthlies = async () => {
      setLoading(true); // Set loading to true when dataFor changes
      try {
        const routeName = dataFor === 'team' ? 'teamstats' : 'userstats';
        const response = await axios.get(route(routeName, ['last30days']), {
          params: {
            event_id: auth.preferred_event.id,
            user_id: auth.user.id,
          },
        });
        setLast30days(response.data);
        setLoading(false);
      } catch (err) {
        console.error('Error fetching monthlies:', err);
        setLoading(false);
      }
    };

    fetchMonthlies();
  }, [dataFor]);

  // Process data to calculate proper 7-day average starting from day 7
  const chartData = useMemo(() => {
    return last30days.map((item: Last30DaysData, index: number) => {
      let sevenDayAvg = null;

      // Only calculate 7-day average starting from day 7 (index 6)
      if (index >= 6) {
        const last7Days = last30days.slice(index - 6, index + 1);
        const sum = last7Days.reduce((total, day) => total + parseFloat((day.daily_total || 0).toString()), 0);
        sevenDayAvg = sum / 7;
      }

      return {
        ...item,
        daily_total: parseFloat((item.daily_total || 0).toString()),
        seven_day_avg: sevenDayAvg,
      };
    });
  }, [last30days]);

  return (
    <>
      {loading ? (
        <Card>
          <CardHeader>
            <Skeleton className="mb-2 h-6 w-full" />
            <Skeleton className="h-4 w-full" />
          </CardHeader>
          <CardContent>
            <div className="flex h-50 w-full items-end gap-2 px-4">
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
          <CardHeader>
            <CardTitle className="text-2xl">Last 30 Days</CardTitle>
            <CardDescription>{dataFor === 'team' ? 'Team' : 'Your'} daily points for the last 30 days</CardDescription>
          </CardHeader>
          <CardContent>
            <ChartContainer config={chartConfig} className='max-h-75 w-full'>
              <ComposedChart width={800} height={50} data={chartData} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis
                  dataKey="label"
                  tickLine={false}
                  tickMargin={10}
                  axisLine={false}
                  tickFormatter={(value) => {
                    // Format date to show month/day
                    const date = new Date(value);
                    return `${date.getMonth() + 1}/${date.getDate()}`;
                  }}
                  interval="preserveStartEnd"
                />
                <YAxis
                  tickLine={false}
                  axisLine={false}
                  tickMargin={10}
                  label={{ value: 'Miles', angle: -90, position: 'insideLeft' }}
                  domain={[0, 'auto']}
                />
                <ChartTooltip
                  content={<ChartTooltipContent />}
                  formatter={(value, name) => [typeof value === 'number' ? value.toFixed(2) : value, name]}
                  labelFormatter={(label) => {
                    const date = new Date(label);
                    return date.toLocaleDateString('en-US', {
                      weekday: 'short',
                      month: 'short',
                      day: 'numeric',
                    });
                  }}
                />
                <Legend />
                <Bar dataKey="daily_total" fill="var(--color-primary)" name="Daily Miles" radius={[2, 2, 0, 0]} />
                <Line
                  type="monotone"
                  dataKey="seven_day_avg"
                  stroke="#f7a35c"
                  strokeWidth={3}
                  dot={{ r: 4, fill: '#f7a35c' }}
                  activeDot={{ r: 6, fill: '#f7a35c' }}
                  name="7-Day Average"
                  connectNulls={false}
                />
              </ComposedChart>
            </ChartContainer>
          </CardContent>
          <CardFooter className="flex-col items-start gap-2 text-sm"></CardFooter>
        </Card>
      )}
    </>
  );
}
