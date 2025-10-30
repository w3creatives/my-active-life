import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Head, router } from '@inertiajs/react';
import {
  Trash2,
  Calendar as CalendarIcon,
  CheckCircle2,
  MessageSquare,
  Image as ImageIcon,
  Edit,
  History as HistoryIcon,
  Wand2,
} from 'lucide-react';
import { useState, useEffect } from 'react';
import { format, parseISO } from 'date-fns';
import AppLayout from '@/layouts/app-layout';
import { toast } from 'sonner';
import PageContent from '@/components/atoms/page-content';
import ContentHeader from '@/components/molecules/ContentHeader';
import type { BreadcrumbItem } from '@/types';

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

interface HistoryProps {
  flash?: {
    success?: string;
    error?: string;
  };
}

export default function Index({ flash }: HistoryProps) {
  const [quests, setQuests] = useState<Quest[]>([]);
  const [loading, setLoading] = useState(true);
  const [deletingId, setDeletingId] = useState<number | null>(null);

  // Fetch archived quests on component mount
  useEffect(() => {
    fetchArchivedQuests();
  }, []);

  const fetchArchivedQuests = async () => {
    try {
      setLoading(true);
      const response = await fetch(route('webapi.quests') + '?is_archived=1', {
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
        toast.error('Failed to load archived quests');
      }
    } catch (error) {
      console.error('Error fetching archived quests:', error);
      toast.error('Failed to load archived quests');
    } finally {
      setLoading(false);
    }
  };

  const handleEdit = (id: number) => {
    router.visit(route('fit-life-activities.edit', id));
  };

  const handleDelete = (id: number) => {
    setDeletingId(id);
    router.delete(route('fit-life-activities.destroy', id), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Quest deleted successfully!');
        fetchArchivedQuests();
      },
      onError: (errors) => {
        toast.error(errors.error || 'Failed to delete quest');
      },
      onFinish: () => {
        setDeletingId(null);
      },
    });
  };

  // Group quests by month
  const groupedByMonth: { [key: string]: Quest[] } = {};
  quests.forEach((quest) => {
    const monthKey = format(new Date(quest.date), 'MMMM yyyy');
    if (!groupedByMonth[monthKey]) {
      groupedByMonth[monthKey] = [];
    }
    groupedByMonth[monthKey].push(quest);
  });

  const monthKeys = Object.keys(groupedByMonth);

  const breadcrumbs: BreadcrumbItem[] = [
    {
      title: 'Home',
      href: route('dashboard'),
    },
    {
      title: 'Quests History',
      href: route('fit-life-activities.history'),
    },
  ];

  const ContentHeaderActions = [
    {
      label: 'Schedule a Quest',
      route: route('fit-life-activities.create'),
      icon: HistoryIcon,
    },
    {
      label: 'Manage Quests',
      route: route('fit-life-activities.history'),
      icon: Wand2,
      variant: 'outline-primary' as const
    }
  ]

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Quest History" />

      <PageContent>
        {/* Flash Messages */}
        {flash?.success && (
          <Alert className="border-green-100 bg-green-50 text-black">
            <AlertDescription>{flash.success}</AlertDescription>
          </Alert>
        )}

        {flash?.error && (
          <Alert className="mb-6 border-red-500 bg-red-50 text-red-900">
            <AlertDescription>{flash.error}</AlertDescription>
          </Alert>
        )}

        <ContentHeader title='Quest History' actions={ContentHeaderActions} />

        {/* Loading State */}
        {loading ? (
          <Card>
            <CardContent className="flex flex-col items-center justify-center gap-4 py-12">
              <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
              <p className="text-muted-foreground">Loading quest history...</p>
            </CardContent>
          </Card>
        ) : quests.length === 0 ? (
          <Card>
            <CardContent className="flex flex-col items-center justify-center gap-4 py-5">
              <CalendarIcon className="size-12 text-muted-foreground" />
              <h3 className="mb-2 text-lg font-semibold">
                You have no archived quests yet...
              </h3>
            </CardContent>
          </Card>
        ) : (
          <div className="space-y-8">
            {monthKeys.map((monthKey) => (
              <div key={monthKey}>
                {/* Month Header */}
                <h3 className="mb-4 flex items-center gap-2 text-xl font-semibold text-primary">
                  <CalendarIcon className="size-4" />
                  {monthKey}
                </h3>

                {/* Quests for this month */}
                <div className="space-y-4">
                  {groupedByMonth[monthKey].map((quest) => {
                    const isProcessing = deletingId === quest.id;

                    return (
                      <Card key={quest.id} className="overflow-hidden transition-shadow hover:shadow-md">
                        <CardContent className="p-0">
                          <div className="flex flex-col gap-4 p-4 md:flex-row md:items-center">
                            {/* Activity Bib Image */}
                            {quest.activity.bib_image && (
                              <div className="flex-shrink-0">
                                <img
                                  src={quest.activity.bib_image}
                                  alt={quest.activity.name}
                                  className="size-25 rounded-lg object-cover"
                                />
                              </div>
                            )}

                            {/* Activity Details */}
                            <div className="flex-1 space-y-2">
                              <div className="flex flex-wrap items-center gap-2">
                                <h3 className="text-lg font-semibold">{quest.activity.name}</h3>
                                {quest.is_completed && (
                                  <Badge variant="default" className="bg-green-500 hover:bg-green-600">
                                    <CheckCircle2 className="size-3" />
                                    Completed
                                  </Badge>
                                )}
                                {quest.notes && (
                                  <Badge variant="outline" className="gap-1">
                                    <MessageSquare className="size-3" />
                                    Has Notes
                                  </Badge>
                                )}
                                {quest.image && (
                                  <Badge variant="outline" className="gap-1">
                                    <ImageIcon className="size-3" />
                                    Has Photo
                                  </Badge>
                                )}
                              </div>

                              <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <CalendarIcon className="size-4" />
                                <span>{format(parseISO(quest.date), 'EEEE, MMMM d, yyyy')}</span>
                              </div>

                              <div className="text-sm">
                                <span className="font-medium">Category:</span> {quest.activity.category} •{' '}
                                <span className="font-medium">Group:</span> {quest.activity.group} •{' '}
                                <span className="font-medium">Points:</span> {quest.activity.total_points}
                              </div>

                              {quest.activity.sponsor && (
                                <div className="text-sm">
                                  <span className="font-medium">Sponsor:</span> {quest.activity.sponsor}
                                </div>
                              )}
                            </div>

                            {/* Action Buttons */}
                            <div className="flex flex-wrap gap-2 md:flex-col md:flex-nowrap min-w-38">
                              <Button
                                variant="outline-primary"
                                size="sm"
                                onClick={() => handleEdit(quest.id)}
                                disabled={isProcessing}
                                className="flex-1 md:flex-none justify-start"
                              >
                                <Edit className="size-4" />
                                <span className="text-center flex-1">Add Notes</span>
                              </Button>

                              <AlertDialog>
                                <AlertDialogTrigger asChild>
                                  <Button
                                    variant="destructive"
                                    size="sm"
                                    disabled={isProcessing}
                                    className="flex-1 md:flex-none justify-start"
                                  >
                                    <Trash2 className="size-4" />
                                    <span className="text-center flex-1">{isProcessing ? 'Deleting...' : 'Delete'}</span>
                                  </Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                  <AlertDialogHeader>
                                    <AlertDialogTitle>Delete Quest?</AlertDialogTitle>
                                    <AlertDialogDescription>
                                      Are you sure you want to delete "{quest.activity.name}" from{' '}
                                      {format(parseISO(quest.date), 'MMMM d, yyyy')}? This action cannot be undone.
                                    </AlertDialogDescription>
                                  </AlertDialogHeader>
                                  <AlertDialogFooter>
                                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                                    <AlertDialogAction
                                      onClick={() => handleDelete(quest.id)}
                                      className="bg-destructive text-white hover:bg-destructive/90"
                                    >
                                      {deletingId === quest.id ? 'Deleting...' : 'Delete'}
                                    </AlertDialogAction>
                                  </AlertDialogFooter>
                                </AlertDialogContent>
                              </AlertDialog>
                            </div>
                          </div>
                        </CardContent>
                      </Card>
                    );
                  })}
                </div>
              </div>
            ))}
          </div>
        )}
      </PageContent>
    </AppLayout>
  );
}
