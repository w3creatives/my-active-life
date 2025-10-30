import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Head, useForm } from '@inertiajs/react';
import { Archive, ArrowLeft, Calendar as CalendarPlus, History as HistoryIcon, Wand2 } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import PageContent from '@/components/atoms/page-content';
import { toast } from 'sonner';
import ContentHeader from '@/components/molecules/ContentHeader';

interface Activity {
  label: string;
  value: string;
}

interface ActivityGroup {
  [groupName: string]: Activity[];
}

interface ScheduleProps {
  activities: {
    byGroup: ActivityGroup;
    descriptions: { [key: string]: string };
  };
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

export default function Schedule({
  activities,
  currentEvent,
  eventStartDate,
  eventEndDate,
  flash,
}: ScheduleProps) {
  const [selectedActivity, setSelectedActivity] = useState<string>('');
  const [activityDescription, setActivityDescription] = useState<string>('');

  const { data, setData, post, processing, errors, reset } = useForm({
    activity_name: '',
    activity_date: '',
    invitees_emails: '',
  });

  const handleActivityChange = (value: string) => {
    setSelectedActivity(value);
    setData('activity_name', value);
    setActivityDescription(activities.descriptions[value] || '');
  };

  const handleDateChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setData('activity_date', e.target.value);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('fit-life-activities.store'), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Quest scheduled successfully!');
        reset();
        setSelectedActivity('');
        setActivityDescription('');
      },
      onError: (errors) => {
        if (errors.error) {
          toast.error(errors.error);
        } else {
          toast.error('Failed to schedule quest. Please check the form and try again.');
        }
      },
    });
  };

  const getEventSpecificText = (text: string) => {
    if (currentEvent.name.startsWith("The Hero's Journey")) {
      return text.replace('Activity', 'Quest').replace('activity', 'quest');
    }
    return text;
  };

  const breadcrumbs: BreadcrumbItem[] = [
    {
      title: 'Home',
      href: route('dashboard'),
    },
    {
      title: 'Schedule Quest',
      href: route('fit-life-activities.create'),
    },
  ];

  const ContentHeaderActions = [
    {
      label: 'Manage Quests',
      route: route('fit-life-activities.create'),
      icon: Wand2,
      variant: 'outline-primary' as const
    },
    {
      label: 'Quest History',
      route: route('fit-life-activities.history'),
      icon: Archive,
      variant: 'outline-primary' as const
    }
  ]

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Schedule ${getEventSpecificText('Activity')}`} />

      <PageContent>
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

          <ContentHeader title='Your Quests' description={`Your quest will be added to your "${currentEvent.name}" calendar.`} actions={ContentHeaderActions} />

          <Card className="max-w-1/2">
            <CardContent>
              <form onSubmit={handleSubmit} className="space-y-6">
                {/* Activity Selection */}
                <div className="space-y-2">
                  <Label htmlFor="activity_name">Quest</Label>
                  <Select value={selectedActivity} onValueChange={handleActivityChange}>
                    <SelectTrigger>
                      <SelectValue placeholder='Select a Quest' />
                    </SelectTrigger>
                    <SelectContent>
                      {Object.entries(activities.byGroup).map(([groupName, groupActivities]) => (
                        <SelectGroup key={groupName}>
                          <SelectLabel className="font-semibold">{groupName}</SelectLabel>
                          {groupActivities.map((activity) => (
                            <SelectItem key={activity.value} value={activity.value}>
                              {activity.label}
                            </SelectItem>
                          ))}
                        </SelectGroup>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.activity_name && (
                    <p className="text-sm text-red-600">{errors.activity_name}</p>
                  )}
                </div>

                {/* Activity Description */}
                {activityDescription && (
                  <div className="rounded-lg border bg-muted/50 p-4">
                    <div
                      className="text-sm"
                      dangerouslySetInnerHTML={{ __html: activityDescription }}
                    />
                  </div>
                )}

                {/* Date Selection */}
                <div className="space-y-2">
                  <Label htmlFor="activity_date">Quest Date</Label>
                  <Input
                    id="activity_date"
                    type="date"
                    value={data.activity_date}
                    onChange={handleDateChange}
                    min={eventStartDate}
                    max={eventEndDate}
                  />
                  {errors.activity_date && (
                    <p className="text-sm text-red-600">{errors.activity_date}</p>
                  )}
                </div>

                {/* Email Invitations */}
                <div className="space-y-2">
                  <Label htmlFor="invitees_emails">
                    Tell someone you are doing this! <span className="text-muted-foreground">(optional)</span>
                  </Label>
                  <p className="text-sm text-muted-foreground">
                    You are more likely to honor your commitments if you share them!
                  </p>
                  <Input
                    id="invitees_emails"
                    type="text"
                    placeholder="email@example.com, another@example.com"
                    value={data.invitees_emails}
                    onChange={(e) => setData('invitees_emails', e.target.value)}
                  />
                  <p className="text-xs text-muted-foreground">Enter comma-separated email addresses</p>
                </div>

                {/* Action Buttons */}
                <div className="flex flex-col gap-3 sm:flex-row">
                  <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                    <CalendarPlus className="size-4" />
                    {processing ? 'Scheduling...' : 'Schedule'}
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
      </PageContent>
    </AppLayout>
  );
}
