import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { Skeleton } from '@/components/ui/skeleton';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { Bar, BarChart, CartesianGrid, Legend, Line, XAxis, YAxis } from 'recharts';

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

export default function Last30days() {
  const { auth } = usePage<SharedData>().props;
  const [loading, setLoading] = useState(true);
  const [last30days, setLast30days] = useState([]);

  useEffect(() => {
    const fetchMonthlies = async () => {
      try {
        const response = await axios.get(route('userstats', ['last30days']), {
          params: {
            event_id: auth.preferred_event.id,
            user_id: auth.user.id,
          },
        });
        setLast30days(response.data);
        setLoading(false);
      } catch (err) {
        console.error('Error fetching monthlies:', err);
      }
    };

    fetchMonthlies();
  }, []);

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
          <CardHeader>
            <CardTitle className="text-2xl">Last 30 Days</CardTitle>
            <CardDescription>Your daily points for the last 30 days</CardDescription>
          </CardHeader>
          <CardContent>
            <ChartContainer config={chartConfig}>
              <BarChart
                accessibilityLayer
                data={last30days.map((item) => ({
                  ...item,
                  daily_total: parseFloat(item.daily_total),
                  seven_day_avg: parseFloat(item.seven_day_avg || 0),
                }))}
              >
                <CartesianGrid vertical={false} />
                <XAxis
                  dataKey="label"
                  tickLine={true}
                  tickMargin={10}
                  axisLine={true}
                  tickCount={12}
                  tickFormatter={(value) => value.slice(0, 10)}
                  allowDataOverflow={true}
                />
                <YAxis
                  tickLine={false}
                  axisLine={true}
                  tickMargin={10}
                  unit="mi"
                  domain={[0, 'auto']} // This ensures Y-axis grows based on max value
                />
                <ChartTooltip cursor={false} content={<ChartTooltipContent />} />
                <Bar dataKey="daily_total" fill="var(--color-primary)" radius={4} name="Daily Miles" />
                <Line
                  type="monotone"
                  dataKey="seven_day_avg"
                  stroke="var(--color-secondary, #f7a35c)"
                  strokeWidth={2.5}
                  dot={{ r: 3, fill: 'var(--color-secondary, #f7a35c)' }}
                  activeDot={{ r: 5 }}
                  name="7-Day Average"
                />
                <Legend />
              </BarChart>
            </ChartContainer>
          </CardContent>
          <CardFooter className="flex-col items-start gap-2 text-sm"></CardFooter>
        </Card>
      )}
    </>
  );
}
