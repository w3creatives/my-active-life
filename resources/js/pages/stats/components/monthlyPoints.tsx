import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { Skeleton } from '@/components/ui/skeleton';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';

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
  const [monthlies, setMonthlies] = useState([]);

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
            <CardTitle className="text-2xl">Monthly Points</CardTitle>
            <CardDescription>Your monthly points for the current event</CardDescription>
          </CardHeader>
          <CardContent>
            <ChartContainer config={chartConfig}>
              <BarChart
                accessibilityLayer
                data={monthlies.map((item: MonthlyPointsData) => ({
                  ...item,
                  amount: parseFloat(item.amount),
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
                <ChartTooltip cursor={false} content={<ChartTooltipContent hideLabel />} />
                <Bar dataKey="amount" fill="var(--color-primary)" radius={4} />
              </BarChart>
            </ChartContainer>
          </CardContent>
          <CardFooter className="flex-col items-start gap-2 text-sm"></CardFooter>
        </Card>
      )}
    </>
  );
}
