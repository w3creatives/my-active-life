import { Head, router, usePage } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Switch } from '@/components/ui/switch';
import { toast } from 'sonner';
import { useState } from 'react';

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
    title: 'Privacy settings',
    href: '/settings/privacy',
  },
];

/**
 * Privacy settings page component
 * Allows users to manage their profile visibility and team privacy settings
 */
export default function Privacy() {
  const { auth } = usePage().props;
  const participations = auth?.participations;
  const [processingEventId, setProcessingEventId] = useState<number | null>(null);

  const setPublicProfile = (eventId: number) => (checked: boolean) => {
    setProcessingEventId(eventId);

    router.post('/settings/update/privacy', {
      event_id: eventId,
      public_profile: checked,
    }, {
      preserveScroll: true,
      onSuccess: (response) => {
        const alert = response.props.alert;

        if (alert?.type === 'success') {
          toast.success(alert.message || 'Privacy settings updated successfully');
        } else if (alert?.message) {
          toast.error(alert.message);
        } else {
          toast.success('Privacy settings updated successfully');
        }
        setProcessingEventId(null);
      },
      onError: (errors) => {
        console.error(errors);
        const errorMessage = errors.event_id || errors.public_profile || 'Failed to update privacy settings';
        toast.error(errorMessage);
        setProcessingEventId(null);
      },
    });
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Privacy settings" />
      <SettingsLayout>
        <div className="space-y-8">
          <div className="space-y-2">
            <HeadingSmall
              title="Privacy"
              description="This area allows you to change your privacy options. If you set your profile to public, other event participants will be able to follow your progress on your fitness journey. By default, all profiles are set to private."
            />
          </div>
          <div className="space-y-6">
            {participations?.map((participation) => (
              <Card key={participation.id}>
                <CardContent className="flex flex-col md:flex-row justify-between gap-2">
                  <div className="space-y-2">
                    <p className="text-2xl font-semibold">{participation.event.name}</p>
                    <img className="max-w-xs" src={participation.event.logo_url} alt={participation.event.name} />
                    <p className="text-muted-foreground">
                      Your profile for this event is <span className="font-semibold">{participation.public_profile ? 'Public' : 'Private'}</span>.
                    </p>
                  </div>
                  <div className="space-y-2">
                    <p>Make my profile public?</p>
                    <Switch
                      name={`public_profile_${participation.event.id}`}
                      checked={participation.public_profile}
                      onCheckedChange={setPublicProfile(participation.event.id)}
                      disabled={processingEventId === participation.event.id}
                    />
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}
