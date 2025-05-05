import { Head, Link } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CheckCircle2, Smartphone, Watch } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Device Syncing',
        href: route('profile.device-sync.edit'),
    },
];

interface DeviceSyncProps {
    appleConnected: boolean;
    fitbitConnected: boolean;
    garminConnected: boolean;
    stravaConnected: boolean;
    fitbitUrl: string;
    garminUrl: string;
    stravaUrl: string;
}

export default function DeviceSync({ appleConnected, fitbitConnected, garminConnected, stravaConnected, fitbitUrl, garminUrl, stravaUrl }: DeviceSyncProps) {
    fitbitConnected = appleConnected = garminConnected = stravaConnected = false;
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Device Syncing" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Data Sources"
                        description="You have configured data synchronization from 3 applications to your account. You can always use manual entry to update your miles in the RTE tracker, you can also sync miles from Garmin, Fitbit, and Strava. To learn more about manual entry and synching please visit the Tutorials page where we have videos that demonstrate each way to enter or sync miles."
                    />

                    <div className="grid gap-4 md:grid-cols-4">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <CardTitle className="text-xl">Apple Health</CardTitle>
                                </div>
                                <CardDescription>
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {appleConnected ? (
                                    <div className="flex justify-between items-center">
                                        <Link method="post" href="#" as="button">
                                            <Button variant="outline" className="courser-pointer text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700">
                                                Disconnect
                                            </Button>
                                        </Link>
                                        <div className="flex items-center text-sm text-green-600 gap-1">
                                            <CheckCircle2 className="h-4 w-4" />
                                            <span>Connected</span>
                                        </div>
                                    </div>
                                ) : (
                                    <a href="#" className="inline-block">
                                        <Button className="cursor-pointer dark:text-white">Connect</Button>
                                    </a>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <CardTitle className="text-xl">Fitbit</CardTitle>
                                </div>
                                <CardDescription>
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {garminConnected ? (
                                    <div className="flex justify-between items-center">
                                        <Link method="post" href="#" as="button">
                                            <Button variant="outline" className="courser-pointer text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700">
                                                Disconnect
                                            </Button>
                                        </Link>
                                        <div className="flex items-center text-sm text-green-600 gap-1">
                                            <CheckCircle2 className="h-4 w-4" />
                                            <span>Connected</span>
                                        </div>
                                    </div>
                                ) : (
                                    <a href={fitbitUrl} className="inline-block">
                                        <Button className="cursor-pointer dark:text-white">Connect</Button>
                                    </a>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <CardTitle className="text-xl">Garmin Connect</CardTitle>
                                </div>
                                <CardDescription>
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {fitbitConnected ? (
                                    <div className="flex justify-between items-center">
                                        <Link method="post" href="#" as="button">
                                            <Button variant="outline" className="courser-pointer text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700">
                                                Disconnect
                                            </Button>
                                        </Link>
                                        <div className="flex items-center text-sm text-green-600 gap-1">
                                            <CheckCircle2 className="h-4 w-4" />
                                            <span>Connected</span>
                                        </div>
                                    </div>
                                ) : (
                                    <a href={garminUrl} className="inline-block">
                                        <Button className="cursor-pointer dark:text-white">Connect</Button>
                                    </a>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <CardTitle className="text-xl">Strava</CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {stravaConnected ? (
                                    <div className="flex justify-between items-center">
                                        <Link method="post" href="#" as="button">
                                            <Button variant="outline" className="courser-pointer text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700">
                                                Disconnect
                                            </Button>
                                        </Link>
                                        <div className="flex items-center text-sm text-green-600 gap-1">
                                            <CheckCircle2 className="h-4 w-4" />
                                            <span>Connected</span>
                                        </div>
                                    </div>
                                ) : (
                                    <a href={stravaUrl} className="inline-block">
                                        <Button className="cursor-pointer dark:text-white">Connect</Button>
                                    </a>
                                )}
                            </CardContent>
                        </Card>

                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
