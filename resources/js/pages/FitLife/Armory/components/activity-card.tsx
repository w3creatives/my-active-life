import { Badge } from '@/components/ui/badge';
import { Calendar, MapPin, Star, Target } from 'lucide-react';

export interface FitLifeActivity {
  id: number;
  name: string;
  description: string;
  category: string;
  group: string;
  sponsor?: string;
  total_points: number;
  tags?: string[];
  social_hashtags?: string[];
  sports?: string[];
  available_from?: string;
  available_until?: string;
  is_completed?: boolean;
  quest_count?: number;
  data?: any;
}

interface ActivityCardProps {
  activity: FitLifeActivity;
  isCompleted?: boolean;
  onClick?: () => void;
  className?: string;
}

export default function ActivityCard({ activity, isCompleted = false, onClick, className = '' }: ActivityCardProps) {
  const formatPoints = (points: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(points);
  };

  const getCategoryColor = (category: string) => {
    const colors: Record<string, string> = {
      'fitness': 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
      'wellness': 'bg-green-500/10 text-green-600 dark:text-green-400',
      'nutrition': 'bg-orange-500/10 text-orange-600 dark:text-orange-400',
      'mindfulness': 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
      'social': 'bg-pink-500/10 text-pink-600 dark:text-pink-400',
      'default': 'bg-gray-500/10 text-gray-600 dark:text-gray-400',
    };
    return colors[category?.toLowerCase()] || colors.default;
  };

  const getCategoryIcon = (category: string) => {
    const icons: Record<string, any> = {
      'fitness': Target,
      'wellness': Star,
      'nutrition': MapPin,
      'mindfulness': Star,
      'social': Star,
      'default': Star,
    };
    const Icon = icons[category?.toLowerCase()] || icons.default;
    return <Icon className="h-4 w-4" />;
  };

  return (
    <div
      className={`group cursor-pointer transition-all duration-300 hover:scale-105 ${
        !isCompleted ? 'opacity-90 hover:opacity-100' : ''
      } ${className}`}
      onClick={onClick}
    >
      <div className="relative">
        <div className="bg-card group-hover:border-primary/30 overflow-hidden rounded-lg border-2 border-transparent shadow-sm transition-all hover:shadow-md">
          {/* Card Header with Category Badge */}
          <div className="relative h-32 bg-gradient-to-br from-primary/5 to-primary/10 p-4">
            <div className="absolute top-2 right-2">
              {isCompleted && (
                <div className="rounded-full bg-green-500 p-1.5 shadow-lg">
                  <Star className="h-4 w-4 fill-white text-white" />
                </div>
              )}
            </div>

            <div className="flex h-full flex-col justify-between">
              <Badge className={`w-fit ${getCategoryColor(activity.category)}`} variant="secondary">
                <span className="flex items-center gap-1">
                  {getCategoryIcon(activity.category)}
                  {activity.category || 'General'}
                </span>
              </Badge>

              <div className="flex items-center justify-between">
                <div className="flex items-center gap-1 text-primary">
                  <Target className="h-5 w-5" />
                  <span className="text-lg font-bold">{formatPoints(activity.total_points)}</span>
                  <span className="text-xs">pts</span>
                </div>

                {activity.quest_count !== undefined && activity.quest_count > 0 && (
                  <Badge variant="outline" className="bg-background/80">
                    <Calendar className="mr-1 h-3 w-3" />
                    {activity.quest_count} scheduled
                  </Badge>
                )}
              </div>
            </div>
          </div>

          {/* Card Content */}
          <div className="p-4">
            <h3 className="mb-2 line-clamp-2 min-h-[3rem] text-base font-semibold leading-tight">
              {activity.name}
            </h3>

            {activity.description && (
              <p className="text-muted-foreground mb-3 line-clamp-2 text-xs leading-relaxed">
                {activity.description}
              </p>
            )}

            {/* Group Badge */}
            {activity.group && (
              <div className="mb-2">
                <Badge variant="outline" className="text-xs">
                  {activity.group}
                </Badge>
              </div>
            )}

            {/* Tags */}
            {activity.tags && activity.tags.length > 0 && (
              <div className="flex flex-wrap gap-1">
                {activity.tags.slice(0, 2).map((tag, index) => (
                  <Badge key={index} variant="secondary" className="text-xs">
                    #{tag}
                  </Badge>
                ))}
                {activity.tags.length > 2 && (
                  <Badge variant="secondary" className="text-xs">
                    +{activity.tags.length - 2}
                  </Badge>
                )}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
