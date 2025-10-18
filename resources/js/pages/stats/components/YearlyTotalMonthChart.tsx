import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartContainer, ChartConfig, ChartTooltip } from '@/components/ui/chart';
import { Skeleton } from '@/components/ui/skeleton';
import { Calendar } from 'lucide-react';
import { useEffect, useState } from 'react';
import { BarChart, Bar, Rectangle, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import axios from 'axios';
import { usePage } from '@inertiajs/react';
import { SharedData } from '@/types';

interface YearlyTotalMonthChartProps {
    dataFor?: string;
}

const chartConfig = {
    amount: {
        label: 'Miles',
        color: 'var(--color-primary)',
    },
} satisfies ChartConfig;

//const chartData = {"data":[{"month":"Jan","Run The Year 2016":0,"Run The Year 2017":0,"Run The Year 2018":0,"Run The Year 2020":0,"Run The Year 2021":0,"Run The Year 2022":"10.25","Run The Year 2023":0,"Run The Year 2024":0,"2025 Miles in 2025":"501","Run The Year 2026":0,"RTE October 2025":0},{"month":"Feb","Run The Year 2016":0,"Run The Year 2017":0,"Run The Year 2018":0,"Run The Year 2020":0,"Run The Year 2021":0,"Run The Year 2022":"10.25","Run The Year 2023":0,"Run The Year 2024":0,"2025 Miles in 2025":0,"Run The Year 2026":0,"RTE October 2025":0},{"month":"Mar","Run The Year 2016":0,"Run The Year 2017":0,"Run The Year 2018":0,"Run The Year 2020":0,"Run The Year 2021":0,"Run The Year 2022":"10.25","Run The Year 2023":0,"Run The Year 2024":0,"2025 Miles in 2025":"111","Run The Year 2026":0,"RTE October 2025":0},{"month":"Apr","Run The Year 2016":0,"Run The Year 2017":0,"Run The Year 2018":0,"Run The Year 2020":0,"Run The Year 2021":0,"Run The Year 2022":"10.25","Run The Year 2023":0,"Run The Year 2024":0,"2025 Miles in 2025":0,"Run The Year 2026":0,"RTE October 2025":0},{"month":"May","Run The Year 2016":0,"Run The Year 2017":0,"Run The Year 2018":0,"Run The Year 2020":0,"Run The Year 2021":0,"Run The Year 2022":"10.25","Run The Year 2023":0,"Run The Year 2024":0,"2025 Miles in 2025":"94.097","Run The Year 2026":0,"RTE October 2025":0},{"month":"Jun","Run The Year 2016":0,"Run The Year 2017":0,"Run The Year 2018":0,"Run The Year 2020":0,"Run The Year 2021":0,"Run The Year 2022":"10.25","Run The Year 2023":0,"Run The Year 2024":0,"2025 Miles in 2025":"261.11999999999995","Run The Year 2026":0,"RTE October 2025":0},{"month":"Jul","Run The Year 2016":0,"Run The Year 2017":0,"Run The Year 2018":0,"Run The Year 2020":0,"Run The Year 2021":0,"Run The Year 2022":"10.25","Run The Year 2023":0,"Run The Year 2024":0,"2025 Miles in 2025":"385.8350000000001","Run The Year 2026":0,"RTE October 2025":0},{"month":"Aug","Run The Year 2016":0,"Run The Year 2017":0,"Run The Year 2018":0,"Run The Year 2020":0,"Run The Year 2021":0,"Run The Year 2022":"10.25","Run The Year 2023":0,"Run The Year 2024":0,"2025 Miles in 2025":0,"Run The Year 2026":0,"RTE October 2025":0},{"month":"Sep","Run The Year 2016":0,"Run The Year 2017":0,"Run The Year 2018":0,"Run The Year 2020":0,"Run The Year 2021":0,"Run The Year 2022":"10.25","Run The Year 2023":0,"Run The Year 2024":0,"2025 Miles in 2025":"39","Run The Year 2026":0,"RTE October 2025":0},{"month":"Oct","Run The Year 2016":0,"Run The Year 2017":0,"Run The Year 2018":0,"Run The Year 2020":0,"Run The Year 2021":0,"Run The Year 2022":"10.25","Run The Year 2023":0,"Run The Year 2024":0,"2025 Miles in 2025":"269","Run The Year 2026":0,"RTE October 2025":0},{"month":"Nov","Run The Year 2016":0,"Run The Year 2017":0,"Run The Year 2018":0,"Run The Year 2020":0,"Run The Year 2021":0,"Run The Year 2022":"10.25","Run The Year 2023":0,"Run The Year 2024":0,"2025 Miles in 2025":0,"Run The Year 2026":0,"RTE October 2025":0},{"month":"Dec","Run The Year 2016":0,"Run The Year 2017":0,"Run The Year 2018":0,"Run The Year 2020":0,"Run The Year 2021":0,"Run The Year 2022":0,"Run The Year 2023":0,"Run The Year 2024":0,"2025 Miles in 2025":0,"Run The Year 2026":0,"RTE October 2025":0}],"events":["Run The Year 2016","Run The Year 2017","Run The Year 2018","Run The Year 2020","Run The Year 2021","Run The Year 2022","Run The Year 2023","Run The Year 2024","2025 Miles in 2025","Run The Year 2026","RTE October 2025"]};
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
export default function YearlyTotalMonthChart({ dataFor = 'you' }: YearlyTotalMonthChartProps) {
    const [loading, setLoading] = useState(false);
    const [chartData, setChartData] = useState([]);

    const { auth } = usePage<SharedData>().props;

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('userstats', ['yearly-month']), {
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
                    <CardTitle className="text-lg">Yearly Miles By Month</CardTitle>
                </div>
                <CardDescription className="text-sm">
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="h-64 w-full overflow-hidden">
                    <ChartContainer config={chartConfig} className="h-full w-full">

                        <ResponsiveContainer>
                            <BarChart data={chartData.data} margin={{ top: 16, right: 24, left: 8, bottom: 8 }}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="month"  tickLine={false}
                                       axisLine={false}
                                       tickMargin={8}
                                       fontSize={12}/>
                                <YAxis  tickLine={false}
                                        axisLine={false}
                                        tickMargin={8}
                                        fontSize={11}/>
                                <Tooltip formatter={(value) => (typeof value === 'number' ? value.toFixed(2) : String(value))} />
                                <Legend />
                                {chartData && chartData.events && chartData.events.map((s:any) => (
                                    <Bar
                                        key={s.name}
                                        dataKey={s.name}

                                        name={s.name}
                                        fill={s.color}
                                        maxBarSize={50}
                                        radius={[6, 6, 0, 0]}
                                    />
                                ))}
                            </BarChart>
                        </ResponsiveContainer>
                    </ChartContainer>
                </div>
            </CardContent>
        </Card>
    );
}
