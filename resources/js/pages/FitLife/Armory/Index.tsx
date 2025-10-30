import PageContent from '@/components/atoms/page-content';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Shield, Trophy } from 'lucide-react';
import { useState, useEffect, useMemo } from 'react';
import { format, parseISO } from 'date-fns';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: route('dashboard'),
  },
  {
    title: 'Armory',
    href: route('fit-life-activities.armory'),
  },
];

interface Activity {
  id: number;
  name: string;
  description: string;
  sponsor: string;
  category: string;
  group: string;
  total_points: number;
  tags: string;
  social_hashtags: string;
  sports: string;
  available_from: string;
  available_until: string;
  data: string;
  bib_image: string;
}

interface Quest {
  id: number;
  date: string;
  notes: string | null;
  data: string | null;
  archived: boolean;
  shared: boolean;
  activity_id: number;
  created_at: string;
  updated_at: string;
  image: string | null;
  is_completed: boolean;
  activity: Activity;
}

interface QuestsResponse {
  success: boolean;
  data: {
    current_page: number;
    data: Quest[];
    first_page_url: string;
    from: number;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
  };
  message: string;
}

export default function ArmoryIndex() {
  const [quests, setQuests] = useState<Quest[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchQuests();
  }, []);

  const fetchQuests = async () => {
    try {
      setLoading(true);
      const response = await fetch(route('webapi.quests'), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
      });

      const result: QuestsResponse = await response.json();

      if (result.success) {
        setQuests(result.data.data);
      } else {
        toast.error('Failed to load shields');
      }
    } catch (error) {
      console.error('Error fetching shields:', error);
      toast.error('Failed to load shields');
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString: string) => {
    const date = parseISO(dateString);
    return format(date, 'MMMM d, yyyy');
  };

  const shieldStats = useMemo(() => {
    const earned = quests.filter((q) => q.is_completed).length;
    const total = quests.length;
    const percentage = total > 0 ? (earned / total) * 100 : 0;
    return { earned, total, percentage };
  }, [quests]);

  if (loading) {
    return (
      <AppLayout breadcrumbs={breadcrumbs}>
        <Head title="Armory" />
        <PageContent>
          <div className="flex min-h-96 flex-col items-center justify-center">
            <div className="size-6 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
            <p className="text-muted-foreground mt-4">Loading shields...</p>
          </div>
        </PageContent>
      </AppLayout>
    );
  }

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Armory" />
      <PageContent>
        <div className="space-y-8">
          {/* Header Section */}
          <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <div className="mb-2 flex items-center gap-2">
                <Shield className="size-6 text-primary" />
                <h1 className="text-2xl font-semibold">Armory</h1>
              </div>
              <p className="text-muted-foreground text-lg">
                Your quest shields collection
              </p>
            </div>
          </div>

          {/* Shields Grid */}
          {quests.length === 0 ? (
            <Card>
              <CardContent className="flex flex-col items-center justify-center gap-4 py-12">
                <Shield className="size-12 text-muted-foreground" />
                <h3 className="mb-2 text-lg font-semibold">
                  No shields in your armory yet
                </h3>
                <p className="text-muted-foreground">
                  Schedule quests to start collecting shields!
                </p>
              </CardContent>
            </Card>
          ) : (
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <div>
                    <CardTitle className="flex items-center gap-2">
                      Quest Shields
                    </CardTitle>
                    <CardDescription>
                      {shieldStats.earned} of {shieldStats.total} shields earned ({shieldStats.percentage.toFixed(1)}%)
                    </CardDescription>
                  </div>
                  <Badge variant="outline" className="px-3 py-1 text-md">
                    {shieldStats.earned}/{shieldStats.total}
                  </Badge>
                </div>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                  {quests.map((quest) => (
                    <div
                      key={quest.id}
                      className={`group transition-all duration-300 hover:scale-105 ${
                        !quest.is_completed ? 'opacity-40 grayscale hover:opacity-100 hover:grayscale-0' : ''
                      }`}
                    >
                      <div className="relative">
                        <div className="bg-muted/20 group-hover:border-primary/20 aspect-square overflow-hidden rounded-lg border-2 border-transparent transition-colors">
                          {quest.activity.bib_image ? (
                            <img
                              src={quest.activity.bib_image}
                              alt={quest.activity.name}
                              className="h-full w-full object-cover"
                              loading="lazy"
                            />
                          ) : (
                            <div className="flex h-full w-full items-center justify-center">
                              <Shield className="text-muted-foreground/50 h-12 w-12" />
                            </div>
                          )}
                        </div>

                        {quest.is_completed && (
                          <div className="absolute -top-2 -right-2 rounded-full bg-green-500 p-1 shadow-lg">
                            <Trophy className="h-3 w-3 text-white" />
                          </div>
                        )}
                      </div>
                      <div className="mt-2 text-center">
                        <p className="truncate px-1 text-sm font-medium">{quest.activity.name}</p>
                        <p className="text-muted-foreground text-xs">{quest.activity.total_points} points</p>
                        <p className="text-muted-foreground text-xs">{formatDate(quest.date)}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      </PageContent>
    </AppLayout>
  );
}
