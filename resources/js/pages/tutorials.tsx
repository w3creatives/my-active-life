import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import VideoCard from '@/components/tutorial/video-card';

type TutorialItem = {
  type: string;
  level?: number;
  content?: string;
  source?: string;
  title?: string;
  thumb?: string;
  url?: string;
};

type TutorialProps = {
  tutorials?: {
    event_id: number;
    tutorial_text: string;
  };
};

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Tutorials',
    href: route('tutorials'),
  },
];

export default function Tutorials({ tutorials }: TutorialProps) {
  const [tutorialItems, setTutorialItems] = useState<TutorialItem[]>([]);
  const [pageTitle, setPageTitle] = useState('Tutorials');

  console.log(tutorialItems);

  useEffect(() => {
    if (tutorials && tutorials.tutorial_text) {
      try {
        const parsedItems = JSON.parse(tutorials.tutorial_text);
        setTutorialItems(parsedItems);

        // Set page title from first heading if available
        const firstHeading = parsedItems.find(item => item.type === 'heading' && item.level === 1);
        if (firstHeading) {
          setPageTitle(firstHeading.content);
        }
      } catch (error) {
        console.error('Error parsing tutorial data:', error);
      }
    }
  }, [tutorials]);

  const renderTutorialItem = (item: TutorialItem, index: number) => {
    switch (item.type) {
      case 'video':
        return (
          <VideoCard
            key={index}
            source={item.source || ''}
            title={item.title}
            thumb={item.thumb}
            url={item.url || ''}
          />
        );
      default:
        return null;
    }
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={pageTitle} />
      <div className="flex flex-col gap-6 p-4">
        <h1 className="text-4xl font-normal">{pageTitle}</h1>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {tutorialItems.map((item, index) => renderTutorialItem(item, index))}
        </div>
      </div>
    </AppLayout>
  );
}
