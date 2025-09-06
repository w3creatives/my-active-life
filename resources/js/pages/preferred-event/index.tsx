import PageContent from '@/components/atoms/page-content';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
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

export default function PreferredEvent({ events }: { events: any[] }) {
  console.log(events);
  const [updatingEventId, setUpdatingEventId] = useState<number | null>(null);
  const [localEvents, setLocalEvents] = useState(events);

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
          setLocalEvents((prevEvents) =>
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

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Your Current Challenges" />
      <PageContent>
        <Heading title="Your Current Challenges" description="Below are the challenges you are registered for with Run The Edge." />

        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {localEvents.map((event) => (
            <Card key={event.id}>
              <CardHeader>
                <CardTitle>{event.name}</CardTitle>
                <CardDescription>{event.description}</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="flex flex-col items-center justify-center gap-4">
                  <p className="text-lg font-semibold">You are registered!</p>
                  <Button variant="default" asChild>
                    <Link href={route('preferred.event', event.id)}>Go To Challenge</Link>
                  </Button>
                </div>
              </CardContent>
              <CardFooter>
                <div className="mt-4 flex items-center gap-2">
                  <Checkbox
                    id={`preferred-${event.id}`}
                    checked={event.is_preferred}
                    onCheckedChange={(checked) => handlePreferredEventChange(event.id, checked as boolean)}
                    disabled={updatingEventId === event.id}
                  />
                  <Label htmlFor={`preferred-${event.id}`}>{updatingEventId === event.id ? 'Updating...' : 'Make this my default'}</Label>
                </div>
              </CardFooter>
            </Card>
          ))}
        </div>
      </PageContent>
    </AppLayout>
  );
}
