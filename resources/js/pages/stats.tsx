import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import BasicStatsCard from '@/components/stats/basic-stats-card';
import AchievementsCards from '@/components/stats/achievements-cards';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Stats',
        href: route('stats'),
    },
];

export default function Stats() {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Stats" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-4xl font-normal">Your Statistics</h1>

                {/* Statistics Cards - Load Independently */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <BasicStatsCard />
                    <AchievementsCards />
                </div>

                {/* Placeholder for future charts */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Progress Chart</CardTitle>
                            <CardDescription>Your progress over time</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="h-64 flex items-center justify-center text-muted-foreground">
                                Chart coming soon...
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Activity Breakdown</CardTitle>
                            <CardDescription>Miles by activity type</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="h-64 flex items-center justify-center text-muted-foreground">
                                Chart coming soon...
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
