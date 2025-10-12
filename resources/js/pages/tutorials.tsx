import EventBannerImage from '@/components/atoms/EventBannerImage';
import VideoCard from '@/components/tutorial/video-card';
import { Card, CardContent } from '@/components/ui/card';
import { SkeletonGrid, VideoCardSkeleton } from '@/components/ui/skeleton-components';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';

type TutorialItem = {
  type: string;
  level?: number;
  content?: string;
  source?: string;
  title?: string;
  thumb?: string;
  url?: string;
};

type TutorialData = {
  event_id: number;
  tutorial_text: string;
};

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: route('dashboard'),
  },
  {
    title: 'Tutorials',
    href: route('tutorials'),
  },
];

export default function Tutorials() {
  const [tutorialItems, setTutorialItems] = useState<TutorialItem[]>([]);
  const [pageTitle, setPageTitle] = useState('Tutorials');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchTutorials = async () => {
      try {
        setLoading(true);
        const response = await axios.get(route('api.tutorials.data'));
        const tutorials: TutorialData = response.data.tutorials;

        if (tutorials && tutorials.tutorial_text) {
          try {
            const parsedItems = JSON.parse(tutorials.tutorial_text);
            setTutorialItems(parsedItems);

            console.log(tutorialItems);

            // Set page title from first heading if available
            const firstHeading = parsedItems.find((item) => item.type === 'heading' && item.level === 1);
            if (firstHeading) {
              setPageTitle(firstHeading.content);
            }
          } catch (parseError) {
            console.error('Error parsing tutorial data:', parseError);
            setError('Failed to parse tutorial data');
          }
        }
      } catch (err) {
        setError('Failed to load tutorials');
        console.error('Error fetching tutorials:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchTutorials();
  }, []);

  const renderTutorialItem = (item: TutorialItem, index: number) => {
    switch (item.type) {
      case 'video':
        return <VideoCard key={index} source={item.source || ''} title={item.title} thumb={item.thumb} url={item.url || ''} />;
      default:
        return null;
    }
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={pageTitle} />
      <div className="flex flex-col gap-6 p-4">
        <EventBannerImage />
        <h1 className="text-4xl font-normal">{pageTitle}</h1>

        {loading ? (
          <SkeletonGrid count={4} columns={2} component={VideoCardSkeleton} />
        ) : error ? (
          <Card>
            <CardContent className="p-6">
              <p className="text-red-500">{error}</p>
            </CardContent>
          </Card>
        ) : (
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">{tutorialItems.map((item, index) => renderTutorialItem(item, index))}</div>
        )}
      </div>
    </AppLayout>
  );
}
