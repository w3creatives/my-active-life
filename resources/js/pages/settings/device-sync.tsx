import { Head, Link } from '@inertiajs/react';

import DeviceSyncCard from '@/components/device-sync-card';
import HeadingSmall from '@/components/heading-small';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: route('dashboard'),
  },
  {
    title: 'Settings',
    href: route('profile.edit'),
  },
  {
    title: 'Device Syncing',
    href: route('profile.device-sync.edit'),
  },
];

interface DeviceSyncProps {
  connectedSources: Array<string>;
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

          <div className="grid gap-4 md:grid-cols-2">
            {/*
                            <DeviceSyncCard
                              name="Device Name"
                              imageSrc="/path/to/image.png"
                              isConnected={true}
                              connectRoute="/connect/device"
                              disconnectRoute="/disconnect/device"
                              onDisconnect={() => {
                                // Update parent component state or perform other actions
                                // For example, remove this device from a list of connected devices
                              }}
                            />
                        */}
            <DeviceSyncCard
              name="Apple Health"
              imageSrc="/storage/dashboard/datasource/rte-trackery.png"
              description="Sync your activities with your Apple Health and earn points in Run The Edge."
              isConnected={connectedSources.includes('apple')}
              connectRoute={route('profile.device-sync.connect', 'apple')}
              disconnectRoute={route('profile.device-sync.disconnect', 'apple')}
              modalContent={
                <>
                  <p>
                    To sync your Apple Watch search for <strong>"Trackery"</strong> in the App Store and download the free app. Once connected, miles
                    from your Apple Watch will sync to the RTE Tracker.
                  </p>
                  <p>
                    Check out this{' '}
                    <Link className="underline" href="">
                      tutorial
                    </Link>{' '}
                    for a step-by-step guide to get set up.
                  </p>
                  <p>Please select a start date for syncing your activities. We'll import all activities from this date forward.</p>
                </>
              }
            />

            <DeviceSyncCard
              name="Fitbit"
              imageSrc="/storage/dashboard/datasource/fitbit.png"
              description="Sync your activities with your Fitbit device and earn points in Run The Edge."
              isConnected={connectedSources.includes('fitbit')}
              connectRoute={route('profile.device-sync.connect', 'fitbit')}
              disconnectRoute={route('profile.device-sync.disconnect', 'fitbit')}
            />

            <DeviceSyncCard
              name="Garmin Connect"
              imageSrc="/storage/dashboard/datasource/garmin.png"
              description="Sync your activities with your Garmin device and earn points in Run The Edge."
              isConnected={connectedSources.includes('garmin')}
              connectRoute={route('profile.device-sync.connect', 'garmin')}
              disconnectRoute={route('profile.device-sync.disconnect', 'garmin')}
              modalContent={
                <>
                  <p>
                    <strong>Know Before You Sync:</strong>
                  </p>
                  <p>
                    If your Garmin is NOT GPS-enabled and you would like miles from your daily steps to be added to the tracker, slide the switch for
                    daily steps ON.
                  </p>
                  <p>
                    If your Garmin IS GPS-enabled all activities will be auto-synced to the tracker. Sliding the switch for daily steps ON will double
                    your miles.
                  </p>
                  <p>You can always slide the switch for daily steps OFF without having to resync your device.</p>
                  <p>
                    <code>event_list</code>
                  </p>
                  <p>Select the date you would like to synchronize miles from. Please allow 30 minutes for data to sync.</p>
                </>
              }
            />

            <DeviceSyncCard
              name="Strava"
              imageSrc="/storage/dashboard/datasource/strava.png"
              description="Sync your activities with your Strava device and earn points in Run The Edge."
              isConnected={connectedSources.includes('strava')}
              connectRoute={route('profile.device-sync.connect', 'strava')}
              disconnectRoute={route('profile.device-sync.disconnect', 'strava')}
            />
          </div>
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}
