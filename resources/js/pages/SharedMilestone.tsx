import { Head } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AppLogo from '@/components/app-logo';

interface Milestone {
  id: number;
  name: string;
  distance: number;
  description: string;
  image_url: string;
  video_url?: string;
}

interface Event {
  id: number;
  name: string;
  hashtags: string;
  registration_url: string;
}

interface SharedMilestoneProps {
  milestone: Milestone;
  event: Event | null;
  shareTitle: string;
  isTeam: boolean;
}

export default function SharedMilestone({ milestone, event, shareTitle, isTeam }: SharedMilestoneProps) {
  const formatDistance = (distance: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 1,
    }).format(distance);
  };

  return (
    <>
      <Head title={milestone.name}>
        <meta property="og:title" content={shareTitle} />
        <meta property="og:description" content={milestone.description || `${formatDistance(milestone.distance)} mile milestone`} />
        <meta property="og:image" content={milestone.image_url} />
        <meta property="og:type" content="website" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content={shareTitle} />
        <meta name="twitter:description" content={milestone.description || `${formatDistance(milestone.distance)} mile milestone`} />
        <meta name="twitter:image" content={milestone.image_url} />
      </Head>

      <div
        className="min-h-screen py-20 flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 p-4"
        style={{backgroundImage: `url('/storage/static/cyclist.jpg')`}}
      >
        <AppLogo className="absolute top-5 left-5" logoColorLight="white" />
        <div className="max-w-lg w-full">
          <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden">
            {/* Header */}
            <div className="bg-primary py-4 px-6 text-white">
              <div className="flex items-center gap-3">
                <Trophy className="h-8 w-8" />
                <div>
                  <h1 className="text-2xl font-bold">{milestone.name}</h1>
                  <p>{formatDistance(milestone.distance)} miles</p>
                </div>
              </div>
            </div>

            {/* Content */}
            <div className="p-6 space-y-6">
              {/* Milestone Image */}
              <div className="flex justify-center">
                <div className="relative max-w-md w-full">
                  <div className="bg-muted/10 aspect-square w-full overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                    {milestone.image_url ? (
                      <img
                        src={milestone.image_url}
                        alt={milestone.name}
                        className="h-full w-full object-contain"
                      />
                    ) : (
                      <div className="flex h-full w-full items-center justify-center">
                        <Trophy className="h-24 w-24 text-gray-300 dark:text-gray-600" />
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* Video */}
              {milestone.video_url && (
                <div className="rounded-lg overflow-hidden">
                  <div className="relative pb-[56.25%]">
                    <iframe
                      src={milestone.video_url.includes('vimeo.com')
                        ? `https://player.vimeo.com/video/${milestone.video_url.split('/').pop()}`
                        : milestone.video_url
                      }
                      className="absolute top-0 left-0 w-full h-full"
                      allow="autoplay; fullscreen; picture-in-picture"
                      allowFullScreen
                    />
                  </div>
                </div>
              )}

              {/* Description */}
              {milestone.description && (
                <div className="text-center">
                  <p className="text-gray-600 dark:text-gray-300">{milestone.description}</p>
                </div>
              )}

              {/* Event Info */}
              {event && (
                <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 text-center space-y-1">
                  <p className="text-sm text-gray-600 dark:text-gray-400">
                    {isTeam ? 'Team' : 'Individual'} milestone from
                  </p>
                  <p className="text-lg font-semibold text-gray-900 dark:text-white">
                    {event.name}
                  </p>
                  {event.hashtags && (
                    <p className="text-sm">
                      {event.hashtags}
                    </p>
                  )}
                </div>
              )}

              {/* Share Message */}
              <div className="border border-primary rounded-lg p-4">
                <p className="text-center text-gray-800 dark:text-gray-200 italic">
                  "{shareTitle}"
                </p>
              </div>

              {/* CTA */}
              <div className="text-center">
                <p className="text-sm text-foreground mb-3">
                  Want to achieve your own milestones?
                </p>
                <Button>
                  <a
                    href={event?.registration_url ? event?.registration_url : `https://runtheedge.com`}
                    className="inline-flex items-center gap-2"
                  >
                    <Trophy className="h-5 w-5" />
                    Join us in the adventure!
                  </a>
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
