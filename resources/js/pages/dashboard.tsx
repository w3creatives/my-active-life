import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Calendar } from '@/components/ui/calendar';
import { AddPointsForm } from '@/components/ui/add-points-form';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: route('dashboard'),
    },
];

interface UserStats {
    total_miles: number;
    best_day: { date: string; miles: number } | null;
    best_week: { yearweek: string; miles: number } | null;
    best_month: { year: number; month: number; miles: number } | null;
}

export default function Dashboard() {
    const [date, setDate] = useState<Date>(new Date());
    const [stats, setStats] = useState<UserStats | null>(null);
    const [eventId, setEventId] = useState<number | null>(null);
    const [loading, setLoading] = useState<boolean>(true);

    const fetchUserStats = async () => {
        try {
            const response = await axios.get(route('user.stats'));
            setStats(response.data.stats);
        } catch (error) {
            console.error('Error fetching user stats:', error);
        }
    };

    const handlePointsAdded = () => {
        // Refresh calendar data and stats
        fetchUserStats();
    };

    useEffect(() => {
        const fetchInitialData = async () => {
            setLoading(true);
            try {
                // Fetch user points to get event ID
                const response = await axios.get(route('user.points'));
                if (response.data.event) {
                    setEventId(response.data.event.id);
                }

                // Fetch user stats
                await fetchUserStats();
            } catch (error) {
                console.error('Error fetching initial data:', error);
            } finally {
                setLoading(false);
            }
        };

        fetchInitialData();
    }, []);

    const formatMonth = (month: number) => {
        const months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        return months[month - 1];
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-6 p-4">
                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Total Miles</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {loading ? '...' : stats?.total_miles || 0}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Best Day</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {loading ? '...' : stats?.best_day?.miles || 0}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {stats?.best_day?.date || 'No data'}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Best Week</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {loading ? '...' : stats?.best_week?.miles || 0}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {stats?.best_week?.yearweek || 'No data'}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Best Month</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {loading ? '...' : stats?.best_month?.miles || 0}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {stats?.best_month ? `${formatMonth(stats.best_month.month)} ${stats.best_month.year}` : 'No data'}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Main Content */}
                <Tabs defaultValue="calendar" className="w-full">
                    <TabsList>
                        <TabsTrigger value="calendar">Calendar</TabsTrigger>
                        <TabsTrigger value="add">Add Points</TabsTrigger>
                    </TabsList>

                    <TabsContent value="calendar" className="mt-4">
                        <Calendar
                            date={date}
                            setDate={setDate}
                        />
                    </TabsContent>

                    <TabsContent value="add" className="mt-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Add Points</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {eventId ? (
                                    <AddPointsForm
                                        eventId={eventId}
                                        onSuccess={handlePointsAdded}
                                    />
                                ) : (
                                    <p className="text-center text-muted-foreground">
                                        You are not participating in any events.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
