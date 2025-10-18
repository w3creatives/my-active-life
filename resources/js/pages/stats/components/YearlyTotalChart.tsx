import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartConfig, ChartContainer } from '@/components/ui/chart';
import { Skeleton } from '@/components/ui/skeleton';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { Calendar } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Bar, BarChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import axios from 'axios';

interface YearlyTotalChartProps {
  dataFor?: string;
}

const chartConfig = {
  amount: {
    label: 'Miles',
    color: 'var(--color-primary)',
  },
} satisfies ChartConfig;

const PreloadChart = () => {
  return (
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
  );
};
export default function YearlyTotalChart({ dataFor = 'you' }: YearlyTotalChartProps) {
  const { auth } = usePage<SharedData>().props;
  const [loading, setLoading] = useState(false);
  const [chartData, setChartData] = useState([]);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('userstats', ['yearly']), {
                params: {
                    event_id: auth.preferred_event.id,
                    user_id: auth.user.id,
                },
            });
            setChartData(response.data);
            setLoading(false);
        } catch (err) {
            console.error('Error fetching data:', err);
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [dataFor]);

  if (loading) {
    return <PreloadChart />;
  }

  return (
    <Card>
      <CardHeader className="pb-3">
        <div className="flex items-center gap-2">
          <Calendar className="text-primary h-5 w-5" />
          <CardTitle className="text-lg">Yearly Total</CardTitle>
        </div>
        <CardDescription className="text-sm"></CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="h-64 w-full overflow-hidden">
          <ChartContainer config={chartConfig} className="h-full w-full">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart layout="horizontal" data={chartData.data} margin={{ top: 10, right: 10, left: 10, bottom: 5 }} barSize={45}>
                <XAxis
                  dataKey="event"
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
                <Tooltip formatter={(value) => (typeof value === 'number' ? value.toFixed(2) : String(value))}/>
                <Legend />
                <Bar dataKey="miles" fill="var(--color-primary)" radius={[6, 6, 0, 0]} maxBarSize={50} />
              </BarChart>
            </ResponsiveContainer>
          </ChartContainer>
        </div>
      </CardContent>
    </Card>
  );
}
