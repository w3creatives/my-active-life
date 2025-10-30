import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dumbbell, Footprints, Heart, Bike, Waves } from 'lucide-react';

export interface JournalEntry {
  id: number;
  date: string;
  activityName: string;
  activityType: string;
  miles?: number;
  note?: string;
  image?: string;
}

interface TimelineItemProps {
  entry: JournalEntry;
  isLast?: boolean;
}

export default function TimelineItem({ entry, isLast = false }: TimelineItemProps) {
    const getActivityIcon = (type: string) => {
    const icons: Record<string, any> = {
      'strength': Dumbbell,
      'running': Footprints,
      'walking': Footprints,
      'cardio': Heart,
      'cycling': Bike,
      'swimming': Waves,
      'default': Heart,
    };
    const Icon = icons[type.toLowerCase()] || icons.default;
    return Icon;
  };

  const getActivityColor = (type: string) => {
    const colors: Record<string, string> = {
      'strength': 'bg-purple-500',
      'running': 'bg-red-500',
      'walking': 'bg-green-500',
      'cardio': 'bg-pink-500',
      'cycling': 'bg-blue-500',
      'swimming': 'bg-primary',
      'default': 'bg-primary',
    };
    return colors[type.toLowerCase()] || colors.default;
  };

  const ActivityIcon = getActivityIcon(entry.activityType);
  const iconColor = getActivityColor(entry.activityType);

  return (
    <div className="relative flex gap-4 pb-8">
      {/* Timeline Line */}
      {!isLast && (
        <div className="absolute left-6 top-14 h-full w-0.5 bg-primary" />
      )}

      {/* Icon Circle */}
      <div className={`relative z-10 flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full ${iconColor} shadow-lg`}>
        <ActivityIcon className="h-6 w-6 text-white" />
      </div>

      {/* Content */}
      <div className="flex-1 pt-1">
        <div className="mb-1">
          <p className="text-muted-foreground text-sm">{entry.date}</p>
        </div>
        <div className="mb-3">
          <h3 className="text-primary text-lg font-semibold hover:underline cursor-pointer">
            {entry.activityName}
          </h3>
        </div>

        {/* Note Card */}
        {entry.note && (
          <div className="relative mb-3 max-w-lg">
            <Card className="border-primary bg-primary py-2 px-4 text-white shadow-md">
              <p className="text-sm font-medium">{entry.note}</p>
            </Card>
            {/* Speech bubble arrow */}
            <div className="absolute -bottom-2 left-8 h-0 w-0 border-l-8 border-r-8 border-t-8 border-l-transparent border-r-transparent border-t-primary" />
          </div>
        )}

        {/* Image */}
        {entry.image && (
          <div className="mb-3 max-w-md">
            <img
              src={entry.image}
              alt={entry.activityName}
              className="rounded-lg border shadow-sm"
            />
          </div>
        )}
      </div>
    </div>
  );
}
