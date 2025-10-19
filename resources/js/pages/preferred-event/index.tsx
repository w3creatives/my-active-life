import PageContent from '@/components/atoms/page-content';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: route('dashboard'),
  },
  {
    title: 'Preferred Event',
    href: route('preferred.event'),
  },
];

interface Event {
  id: number;
  name: string;
  description: string;
  logo_url: string;
  start_date: string;
  end_date: string;
  event_type: string;
  is_preferred?: boolean;
  participation_id?: number;
  is_happening_now?: boolean;
  is_future?: boolean;
  is_past?: boolean;
  is_closing_soon?: boolean;
  subscription_end_date?: string;
  registration_url?: string;
}

interface PreferredEventProps {
  currentChallenges: Event[];
  otherChallenges: Event[];
  pastChallenges: Event[];
}

export default function PreferredEvent({
  currentChallenges,
  otherChallenges,
  pastChallenges,
}: PreferredEventProps) {
  const [updatingEventId, setUpdatingEventId] = useState<number | null>(null);
  const [localCurrentChallenges, setLocalCurrentChallenges] = useState(currentChallenges);

  const handlePreferredEventChange = (eventId: number, isChecked: boolean) => {
    if (!isChecked || updatingEventId) return;

    setUpdatingEventId(eventId);

    router.post(
      route('user.set-preferred-event'),
      { event_id: eventId },
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Preferred event updated successfully');
          // Update local state to reflect the change
          setLocalCurrentChallenges((prevEvents) =>
            prevEvents.map((event) => ({
              ...event,
              is_preferred: event.id === eventId,
            })),
          );
        },
        onError: (errors) => {
          toast.error(errors.error || 'Failed to update preferred event');
        },
        onFinish: () => setUpdatingEventId(null),
      },
    );
  };

  const handleGoToChallenge = (eventId: number) => {
    router.post(
      route('user.set-preferred-event'),
      { event_id: eventId },
      {
        onSuccess: () => {
          router.visit(route('dashboard'));
        },
        onError: (errors) => {
          toast.error(errors.error || 'Failed to set preferred event');
        },
      },
    );
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Your Current Challenges" />
      <PageContent>
        {/* Current Challenges Section */}
        {localCurrentChallenges.length > 0 && (
          <div className="mb-12">
            <Heading
              title="Your Current Challenges"
              description="Below are the challenges you are registered for with Run The Edge."
            />

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
              {localCurrentChallenges.map((event) => (
                <Card key={event.id} className="flex flex-col pt-0">
                  <CardHeader className="p-0">
                    <img
                      src={event.logo_url}
                      alt={event.name}
                      className="w-full rounded-t-xl object-cover"
                      onError={(e) => {
                        e.currentTarget.src = '/images/default-placeholder.png';
                      }}
                    />
                  </CardHeader>
                  <CardContent className="flex flex-1 flex-col items-center justify-center pt-6 text-center">
                    <h5 className="mb-2 text-lg font-semibold text-green-700">You are registered!</h5>
                    {/*
                    {event.is_closing_soon && (
                      <p className="text-sm text-orange-600">Subscription closing soon!</p>
                    )}
                    */}
                  </CardContent>
                  <CardFooter className="flex flex-col gap-4">
                    <Button
                      variant="default"
                      className="w-full"
                      onClick={() => handleGoToChallenge(event.id)}
                    >
                      Go To Challenge
                    </Button>
                    <div className="flex items-center gap-2">
                      <Checkbox
                        id={`preferred-${event.id}`}
                        className="cursor-pointer"
                        checked={event.is_preferred}
                        onCheckedChange={(checked) =>
                          handlePreferredEventChange(event.id, checked as boolean)
                        }
                        disabled={updatingEventId === event.id}
                      />
                      <Label className="cursor-pointer" htmlFor={`preferred-${event.id}`}>
                        {updatingEventId === event.id ? 'Updating...' : 'Make this my default'}
                      </Label>
                    </div>
                  </CardFooter>
                </Card>
              ))}
            </div>
          </div>
        )}

        {/* Other Challenges Section */}
        {otherChallenges.length > 0 && (
          <div className="mb-12">
            <Heading
              title="Other Run The Edge Challenges"
              description="Below are even more great challenges you can take with Run The Edge."
            />

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
              {otherChallenges.map((event) => (
                <Card key={event.id} className="flex flex-col pt-0">
                  <CardHeader className="p-0">
                    <img
                      src={event.logo_url}
                      alt={event.name}
                      className="w-full rounded-t-xl object-cover max-h-22"
                      onError={(e) => {
                        e.currentTarget.src = '/images/default-placeholder.png';
                      }}
                    />
                  </CardHeader>
                  <CardContent className="flex flex-1 flex-col items-center justify-start pt-6 text-center">
                    <h5 className="text-xl">{event.name}</h5>
                    <h5 className="mb-2 text-lg font-semibold">
                      {event.is_happening_now ? 'Tracker is open now!' : 'Coming soon!'}
                    </h5>
                    <p className="text-sm text-muted-foreground">
                      You can join this challenge at any time!
                    </p>
                    <p className="mt-4 text-sm">{event.description}</p>
                  </CardContent>
                  <CardFooter>
                    <Button variant="default" className="w-full" asChild>
                      <a href={event.registration_url} target="_blank" rel="noopener noreferrer">
                        Learn More
                      </a>
                    </Button>
                  </CardFooter>
                </Card>
              ))}
            </div>
          </div>
        )}

        {/* Past Challenges Section */}
        {pastChallenges.length > 0 && (
          <div className="mb-12">
            <Heading
              title="Your Past Challenges"
              description="Click on an event below to view your summary data."
            />

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
              {pastChallenges.map((event) => (
                <Link
                  key={event.id}
                  href="#"
                  className="block transition-transform hover:scale-105"
                >
                  <Card className="flex flex-col pt-0">
                    <CardHeader className="p-0">
                      <img
                        src={event.logo_url}
                        alt={event.name}
                        className="w-full rounded-t-xl object-cover max-h-22"
                        onError={(e) => {
                          e.currentTarget.src = '/images/default-placeholder.png';
                        }}
                      />
                    </CardHeader>
                    <CardContent className="flex flex-1 flex-col items-center justify-center pt-6 text-center">
                      <p className="text-sm text-muted-foreground">{event.description}</p>
                    </CardContent>
                  </Card>
                </Link>
              ))}
            </div>
          </div>
        )}

        {/* No Events Message */}
        {localCurrentChallenges.length === 0 &&
          otherChallenges.length === 0 &&
          pastChallenges.length === 0 && (
            <div className="flex flex-col items-center justify-center py-12 text-center">
              <h2 className="mb-2 text-2xl font-semibold">No Challenges Found</h2>
              <p className="text-muted-foreground">
                You haven't registered for any challenges yet. Explore available challenges to get
                started!
              </p>
            </div>
          )}
      </PageContent>
    </AppLayout>
  );
}
