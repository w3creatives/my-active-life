import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Head, useForm, router } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { format, isPast, isToday } from 'date-fns';
import { toast } from 'sonner';
import AppLayout from '@/layouts/app-layout';
import PageContent from '@/components/atoms/page-content';
import PageTitle from '@/components/atoms/PageTitle';
import ContentHeader from '@/components/molecules/ContentHeader';
import type { BreadcrumbItem } from '@/types';

interface Activity {
  id: number;
  name: string;
  description: string;
  group: string;
  total_points: number;
  logo_image?: string;
  bib_image?: string;
}

interface Registration {
  id: number;
  user_id: number;
  activity_id: number;
  date: string;
  notes: string | null;
  archived: boolean;
  activity: Activity;
}

interface EditProps {
  registration: Registration;
  datesWithRegistrations: string[];
  currentEvent: {
    id: number;
    name: string;
    event_type: string;
  };
  eventStartDate: string;
  eventEndDate: string;
  flash?: {
    success?: string;
    error?: string;
  };
}

export default function Edit({
  registration,
  datesWithRegistrations,
  currentEvent,
  eventStartDate,
  eventEndDate,
  flash,
}: EditProps) {
  const { data, setData, post, processing, errors } = useForm({
    activity_date: registration.date,
    notes: registration.notes || '',
    _method: 'PUT',
  });

  const handleDateChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setData('activity_date', e.target.value);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('fit-life-activities.update', registration.id), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Quest updated successfully!');
      },
      onError: (errors) => {
        if (errors.error) {
          toast.error(errors.error);
        } else {
          toast.error('Failed to update quest. Please check the form and try again.');
        }
      },
    });
  };

  const isActivityPast = () => {
    const activityDate = new Date(registration.date);
    return isPast(activityDate) && !isToday(activityDate);
  };

  const isDatePickerDisabled = isActivityPast();

  const breadcrumbs: BreadcrumbItem[] = [
    {
      title: 'Home',
      href: route('dashboard'),
    },
    {
      title: "Edit Quest",
      href: '#',
    },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Edit Quest" />
      <PageContent>
        <div className="max-w-full xl:max-w-1/2">
          {flash?.success && (
            <Alert className="mb-6 border-green-500 bg-green-50 text-green-900">
              <AlertDescription>{flash.success}</AlertDescription>
            </Alert>
          )}

          {flash?.error && (
            <Alert className="mb-6 border-red-500 bg-red-50 text-red-900">
              <AlertDescription>{flash.error}</AlertDescription>
            </Alert>
          )}

          <ContentHeader title='Edit Quests' />

          <Card>
            <CardContent>
              {/* Quest Bib Image */}
              {registration.activity.bib_image && (
                <div className="mb-6 flex justify-center">
                  <img
                    src={registration.activity.bib_image}
                    alt={registration.activity.name}
                    className="size-32 rounded-lg object-cover"
                  />
                </div>
              )}

              {/* Activity Name */}
              <h2 className="mb-2 text-center text-2xl font-semibold">{registration.activity.name}</h2>

              {/* Current Quest Date */}
              <div className="mb-6 text-center">
                <p className="text-sm text-muted-foreground">
                  Scheduled for: <span className="font-medium text-foreground">{format(new Date(registration.date), 'EEEE, MMMM d, yyyy')}</span>
                </p>
              </div>

              <form onSubmit={handleSubmit} className="space-y-6">
                {/* Date Selection */}
                <div className="space-y-2">
                  <Label htmlFor="activity_date">Activity Date</Label>
                  <Input
                    id="activity_date"
                    type="date"
                    value={data.activity_date}
                    onChange={handleDateChange}
                    disabled={isDatePickerDisabled}
                    min={eventStartDate}
                    max={eventEndDate}
                    className={isDatePickerDisabled ? 'bg-muted' : ''}
                  />
                  {isDatePickerDisabled && (
                    <p className="text-sm text-muted-foreground">
                      Date cannot be changed for past activities
                    </p>
                  )}
                  {errors.activity_date && (
                    <p className="text-sm text-red-600">{errors.activity_date}</p>
                  )}
                </div>

                {/* Notes */}
                <div className="space-y-2">
                  <Label htmlFor="notes">Notes</Label>
                  <Textarea
                    id="notes"
                    placeholder="Another win for me! I am really really awesome!"
                    value={data.notes}
                    onChange={(e) => setData('notes', e.target.value)}
                    rows={5}
                  />
                  {errors.notes && (
                    <p className="text-sm text-red-600">{errors.notes}</p>
                  )}
                </div>

                {/* Action Buttons */}
                <div className="flex flex-col gap-3 sm:flex-row">
                  <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                    <Save className="size-4" />
                    {processing ? 'Saving...' : 'Save Quest'}
                  </Button>
                  <Button
                    type="button"
                    variant="outline-primary"
                    onClick={() => window.history.back()}
                    className="w-full sm:w-auto"
                  >
                    <ArrowLeft className="size-4" />
                    Back To Quests
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>
        </div>
      </PageContent>
    </AppLayout>
  );
}
