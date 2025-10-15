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
            <Card key={event.id} className='pt-0'>
              <CardHeader className='p-0'>
                <img src={event.logo_url} alt={event.name} className="w-full rounded-t-xl" onError={(e) => {e.currentTarget.src="/images/default-placeholder.png";}}/>
              </CardHeader>
              <CardContent>
                <div className="flex flex-col items-center justify-center gap-4">
                  <h2 className='text-2xl font-semibold tracking-tight'>{event.name}</h2>
                  <p className="text-lg text-muted-foreground">You are registered!</p>
                  <Button variant="default" asChild className='mt-4'>
                    <Link href={route('preferred.event', event.id)}>Go To Challenge</Link>
                  </Button>
                </div>
              </CardContent>
              <CardFooter>
                <div className="mt-4 flex items-center gap-2">
                  <Checkbox
                    id={`preferred-${event.id}`}
                    className='cursor-pointer'
                    checked={event.is_preferred}
                    onCheckedChange={(checked) => handlePreferredEventChange(event.id, checked as boolean)}
                    disabled={updatingEventId === event.id}
                  />
                  <Label className='cursor-pointer' htmlFor={`preferred-${event.id}`}>{updatingEventId === event.id ? 'Updating...' : 'Make this my default'}</Label>
                </div>
              </CardFooter>
            </Card>
          ))}
        </div>
      </PageContent>
    </AppLayout>
  );
}
