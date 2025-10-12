import HeadingSmall from '@/components/heading-small';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Activity, Bike, CheckCircle2, MoreHorizontal, Target, Waves } from 'lucide-react';
import { useState, useEffect, useCallback } from 'react';
import { toast } from 'sonner';

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
    title: 'RTY Goals',
    href: '/settings/rty-goals',
  },
];

/**
 * RTY Goals Settings Page Component
 * Allows users to set their yearly mileage goals and configure which activity types to include
 */
export default function RtyGoals({ eventId }: {
  eventId?: number;
}) {
  const { auth } = usePage().props as any;
  const { flash } = usePage().props as { flash: { success?: string; error?: string } };

  // Normalize goals from event to ensure it's a clean array of strings
  const normalizeGoals = (input: unknown): string[] => {
    if (!input) return [];
    // Already an array
    if (Array.isArray(input)) {
      return input
        .map((item: any) => {
          if (item == null) return null;
          // Handle array of objects with value property
          if (typeof item === 'object' && 'value' in item) return String(item.value);
          return String(item);
        })
        .filter(Boolean) as string[];
    }
    // JSON string
    if (typeof input === 'string') {
      const str = input.trim();
      try {
        const parsed = JSON.parse(str);
        if (Array.isArray(parsed)) {
          return parsed.map((v) => String(v));
        }
      } catch (_) {
        // Not JSON; try comma/semicolon/whitespace-delimited
        const parts = str.split(/[\s,;]+/).filter(Boolean);
        if (parts.length) return parts.map((v) => String(v));
      }
    }
    return [];
  };

  const goals = normalizeGoals(auth?.preferredEvent?.goals ?? auth?.preferred_event?.goals);

  const [goal, setGoal] = useState('');
  const [includeBiking, setIncludeBiking] = useState(false);
  const [includeSwimming, setIncludeSwimming] = useState(false);
  const [includeOther, setIncludeOther] = useState(false);

  // Show flash messages
  useEffect(() => {
    if (flash?.success) {
      toast.success(flash.success);
    }
    if (flash?.error) {
      toast.error(flash.error);
    }
  }, [flash]);

  /**
   * Load current RTY goal settings and modalities
   */
  const loadCurrentSettings = useCallback(async () => {
    try {
      const currentEventId = eventId || auth?.preferredEvent?.id;

      // Fetch goal
      const goalResponse = await fetch(`/settings/rty-goals/goal?event_id=${currentEventId}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (goalResponse.ok) {
        const goalData = await goalResponse.json();
        if (goalData.success && goalData.data.rty_mileage_goal) {
          setGoal(goalData.data.rty_mileage_goal.toString());
        }
      }

      // Fetch modalities
      const modalitiesResponse = await fetch(`/settings/rty-goals/modalities?event_id=${currentEventId}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (modalitiesResponse.ok) {
        const modalitiesData = await modalitiesResponse.json();
        if (modalitiesData.success && modalitiesData.data.modalities) {
          interface Modality {
            name: string;
            enabled: boolean;
          }
          const modalities: Modality[] = modalitiesData.data.modalities;
          setIncludeBiking(modalities.find((m) => m.name === 'bike')?.enabled || false);
          setIncludeSwimming(modalities.find((m) => m.name === 'swim')?.enabled || false);
          setIncludeOther(modalities.find((m) => m.name === 'other')?.enabled || false);
        }
      }
    } catch (error) {
      console.error('Failed to load current settings:', error);
      toast.error('Failed to load settings');
    }
  }, [eventId, auth?.preferredEvent?.id]);

  // Load current settings on component mount
  useEffect(() => {
    if (eventId || auth?.preferredEvent?.id) {
      loadCurrentSettings();
    }
  }, [eventId, auth?.preferredEvent?.id, loadCurrentSettings]);

  /**
   * Handle goal selection change
   */
  const handleGoalChange = (selectedGoal: string) => {
    setGoal(selectedGoal);

    if (!eventId && !auth?.preferredEvent?.id) {
      toast.error('No event selected');
      return;
    }

    router.post('/settings/rty-goals/goal', {
      mileage_goal: selectedGoal,
      event_id: eventId || auth?.preferredEvent?.id,
    }, {
      preserveScroll: true,
      onError: () => {
        loadCurrentSettings();
      },
    });
  };

  /**
   * Handle modality change
   */
  const handleModalityChange = (modalityName: string, enabled: boolean) => {
    // Update local state immediately for better UX
    if (modalityName === 'bike') setIncludeBiking(enabled);
    if (modalityName === 'swim') setIncludeSwimming(enabled);
    if (modalityName === 'other') setIncludeOther(enabled);

    if (!eventId && !auth?.preferredEvent?.id) {
      toast.error('No event selected');
      return;
    }

    router.post('/settings/rty-goals/modality', {
      name: modalityName,
      enabled: enabled,
      event_id: eventId || auth?.preferredEvent?.id,
    }, {
      preserveScroll: true,
      onError: () => {
        loadCurrentSettings();
      },
    });
  };

  const ActivityRow = ({
    icon: Icon,
    iconColor,
    label,
    description,
    checked,
    onCheckedChange,
  }: {
    icon: React.FC<{ className?: string }>;
    iconColor: string;
    label: string;
    description: string;
    checked: boolean;
    onCheckedChange: (checked: boolean) => void;
  }) => (
    <div className="flex items-center justify-between space-x-4 py-2">
      <div className="flex items-center space-x-3">
        <Icon className={`size-5 text-${iconColor}-500`} />
        <div className="space-y-1">
          <Label htmlFor={`enable-${label.toLowerCase().replace(' ', '-')}-miles`} className="cursor-pointer text-sm font-medium">
            {label}
          </Label>
          <p className="text-sm text-muted-foreground">{description}</p>
        </div>
      </div>
      <Switch
        id={`enable-${label.toLowerCase().replace(' ', '-')}-miles`}
        checked={checked}
        onCheckedChange={onCheckedChange}
      />
    </div>
  );

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="RTY Goals" />

      <SettingsLayout>
        <div className="space-y-8">
          <HeadingSmall title="Run The Year Goals" description="Run The Year your way! Pick a goal that is right for you!" />

          <div className="grid gap-6">
            {/* Goal Selection Card */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Target className="text-primary h-5 w-5" />
                  Run The Year your way! Pick a goal that is right for you!
                </CardTitle>
                <CardDescription>My mileage goal for {auth?.preferred_event?.name} is:</CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="space-y-2">
                  <Label className="text-sm font-medium block">Choose your mileage goal for {auth?.preferred_event?.name}:</Label>

                  {/* Goal Selection Cards - Single Row with Equal Width */}
                  <div className="flex flex-wrap gap-3">
                    {goals.length === 0 && (
                      <div className="text-sm text-muted-foreground">No goals configured for this event.</div>
                    )}
                    {goals.map((goalOption) => (
                      <div
                        key={goalOption}
                        onClick={() => handleGoalChange(goalOption)}
                        className={`relative cursor-pointer rounded-lg border-2 px-4 py-3 transition-all duration-200 hover:shadow-sm flex-1 max-w-25 ${
                          goal === goalOption ? 'border-primary bg-primary/5 shadow-sm' : 'border-border hover:border-primary/50'
                        } `}
                      >
                        {/* Selection Indicator */}
                        {goal === goalOption && (
                          <div className="absolute -top-1 -right-1">
                            <CheckCircle2 className="text-primary bg-background size-4 rounded-full" />
                          </div>
                        )}

                        {/* Goal Content */}
                        <div className="text-center">
                          <div className="text-primary text-lg font-semibold">{goalOption}</div>
                          <div className="text-muted-foreground text-xs">Miles</div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Activity Types Card */}
            <Card className='shadow-sm'>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Activity className="text-primary size-5" />
                  Activity Types to Include
                </CardTitle>
                <CardDescription>
                  The default settings of RTY only include miles accumulated on your feet, such as running, walking, stepping, etc. You can add extra
                  miles here by enabling each activity type.
                </CardDescription>
              </CardHeader>
              <CardContent className="divide-y">
                {/* Biking Miles */}
                <ActivityRow
                  icon={Bike}
                  iconColor="blue"
                  label="Biking Miles"
                  description="I want my biking miles to be included in my totals"
                  checked={includeBiking}
                  onCheckedChange={(checked) => handleModalityChange('bike', checked)}
                />
                {/* Swimming Miles */}
                <ActivityRow
                  icon={Waves}
                  iconColor="cyan"
                  label="Swimming Miles"
                  description="I want my swimming miles to be included in my totals"
                  checked={includeSwimming}
                  onCheckedChange={(checked) => handleModalityChange('swim', checked)}
                />
                {/* Other Miles */}
                <ActivityRow
                  icon={MoreHorizontal}
                  iconColor="purple"
                  label="Other Miles"
                  description="I want my other miles to be included in my totals"
                  checked={includeOther}
                  onCheckedChange={(checked) => handleModalityChange('other', checked)}
                />

                <div className="bg-muted/50 rounded-lg p-4 mt-4">
                  <p className="text-muted-foreground text-sm">
                    <strong>Note:</strong> Miles qualifying as other miles vary by platform. Garmin, Fitbit, Strava, Apple are all different in that
                    regard.
                  </p>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}
