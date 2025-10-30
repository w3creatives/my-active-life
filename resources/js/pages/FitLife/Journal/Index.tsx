import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import PageContent from '@/components/atoms/page-content';
import { BookOpen } from 'lucide-react';
import { useState, useEffect } from 'react';
import { format, parseISO } from 'date-fns';
import { toast } from 'sonner';
import { Card, CardContent } from '@/components/ui/card';
import TimelineItem, { type JournalEntry } from './components/timeline-item';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: route('dashboard'),
  },
  {
    title: 'My Journal',
    href: route('fit-life-activities.journal'),
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

export default function JournalIndex() {
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
        // Filter quests that have notes or images
        setQuests(result.data.data);
      } else {
        toast.error('Failed to load journal entries');
      }
    } catch (error) {
      console.error('Error fetching journal entries:', error);
      toast.error('Failed to load journal entries');
    } finally {
      setLoading(false);
    }
  };

  // Convert Quest data to JournalEntry format
  const journalEntries: JournalEntry[] = quests.map((quest) => ({
    id: quest.id,
    date: format(parseISO(quest.date), 'MMMM d, yyyy'),
    activityName: quest.activity.name,
    activityType: quest.activity.category.toLowerCase(),
    miles: quest.activity.total_points / 100, // Approximate miles based on points
    note: quest.notes || undefined,
    image: quest.image || undefined,
  }));

  console.log(journalEntries);

  if (loading) {
    return (
      <AppLayout breadcrumbs={breadcrumbs}>
        <Head title="My Journal" />
        <PageContent>
          <div className="flex min-h-96 flex-col items-center justify-center">
            <div className="size-6 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
            <p className="text-muted-foreground mt-4">Loading journal entries...</p>
          </div>
        </PageContent>
      </AppLayout>
    );
  }

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="My Journal" />
      <PageContent>
        <div className="space-y-8">
          {/* Header */}
          <div className="flex flex-col gap-2">
            <div className="flex items-center gap-3">
              <BookOpen className="size-6 text-primary" />
              <h1 className="text-2xl font-semibold">My Journal</h1>
            </div>
            <p className="text-muted-foreground text-lg">
              Your journal uses notes and pictures you add to your Quests.
            </p>
          </div>

          {/* Timeline */}
          <div className="w-full">
            {journalEntries.length > 0 ? (
              <div className="space-y-0">
                {journalEntries.map((entry, index) => (
                  <TimelineItem
                    key={entry.id}
                    entry={entry}
                    isLast={index === journalEntries.length - 1}
                  />
                ))}
              </div>
            ) : (
              <Card>
                <CardContent className="flex flex-col items-center justify-center gap-4 py-12">
                  <BookOpen className="text-muted-foreground/50 h-16 w-16" />
                  <h3 className="text-muted-foreground mb-2 text-xl font-semibold">
                    No Journal Entries Yet
                  </h3>
                  <p className="text-muted-foreground/70">
                    Start adding notes and photos to your quests to see them here!
                  </p>
                </CardContent>
              </Card>
            )}
          </div>
        </div>
      </PageContent>
    </AppLayout>
  );
}
