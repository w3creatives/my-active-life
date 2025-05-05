import { Head, Link } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CheckCircle2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Device Syncing',
        href: route('profile.device-sync.edit'),
    },
];

interface DeviceSyncProps {
    connectedSources: Array<string>
}

export default function DeviceSync({ connectedSources }: DeviceSyncProps) {
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
                                {connectedSources.includes('apple') ? (
                                    <div className="flex justify-between items-center">
                                        <Button
                                            variant="outline"
                                            className="courser-pointer text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700"
                                            asChild
                                        >
                                            <Link method="post" href="#">
                                                Disconnect
                                            </Link>
                                        </Button>
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
                                {connectedSources.includes('fitbit') ? (
                                    <div className="flex justify-between items-center">
                                        <Button
                                            variant="outline"
                                            className="courser-pointer text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700"
                                            asChild
                                        >
                                            <Link
                                                method="post"
                                                href={route('profile.device-sync.disconnect', 'fitbit')}
                                                className="courser-pointer"
                                            >
                                                Disconnect
                                            </Link>
                                        </Button>
                                        <div className="flex items-center text-sm text-green-600 gap-1">
                                            <CheckCircle2 className="h-4 w-4" />
                                            <span>Connected</span>
                                        </div>
                                    </div>
                                ) : (
                                    <a href={route('profile.device-sync.connect', 'fitbit')} className="inline-block">
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
                                {connectedSources.includes('garmin') ? (
                                    <div className="flex justify-between items-center">
                                        <Button
                                            variant="outline"
                                            className="courser-pointer text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700"
                                            asChild
                                        >
                                            <Link method="post" href="#">
                                                Disconnect
                                            </Link>
                                        </Button>
                                        <div className="flex items-center text-sm text-green-600 gap-1">
                                            <CheckCircle2 className="h-4 w-4" />
                                            <span>Connected</span>
                                        </div>
                                    </div>
                                ) : (
                                    <a href={route('profile.device-sync.connect', 'garmin')} className="inline-block">
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
                                {connectedSources.includes('strava') ? (
                                    <div className="flex justify-between items-center">
                                        <Button
                                            variant="outline"
                                            className="courser-pointer text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700"
                                            asChild
                                        >
                                            <Link method="post" href={route('profile.device-sync.disconnect', 'strava')}>
                                                Disconnect
                                            </Link>
                                        </Button>
                                        <div className="flex items-center text-sm text-green-600 gap-1">
                                            <CheckCircle2 className="h-4 w-4" />
                                            <span>Connected</span>
                                        </div>
                                    </div>
                                ) : (
                                    <a href={route('profile.device-sync.connect', 'strava')} className="inline-block">
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
