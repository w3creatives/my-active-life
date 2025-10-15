import { Trophy } from 'lucide-react';

export interface Milestone {
  id: number;
  name: string;
  description: string;
  distance: number;
  is_completed: boolean;
  is_team_completed: boolean;
  logo_image_url: string;
  team_logo_image_url: string;
  video_url?: string;
}

interface TrophyCardProps {
  milestone: Milestone;
  isCompleted: boolean;
  showTeamView?: boolean;
  onClick?: () => void;
  className?: string;
}

export default function TrophyCard({ milestone, isCompleted, showTeamView = false, onClick, className = '' }: TrophyCardProps) {
  const formatDistance = (distance: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 1,
    }).format(distance);
  };

  return (
    <div
      className={`group cursor-pointer transition-all duration-300 hover:scale-105 ${
        !isCompleted ? 'opacity-30 grayscale hover:opacity-100 hover:grayscale-0' : ''
      } ${className}`}
      onClick={onClick}
    >
      <div className="relative">
        <div className="bg-muted/20 group-hover:border-primary/20 aspect-square overflow-hidden rounded-lg border-2 border-transparent transition-colors">
          {milestone.logo_image_url ? (
            <img
              src={showTeamView ? milestone.team_logo_image_url || milestone.logo_image_url : milestone.logo_image_url}
              alt={milestone.name}
              className="h-full w-full object-contain"
              loading="lazy" onError={(e) => {e.currentTarget.src="/images/default-placeholder.png";}}
            />
          ) : (
            <div className="flex h-full w-full items-center justify-center">
              <Trophy className="text-muted-foreground/50 h-12 w-12" />
            </div>
          )}
        </div>

        {isCompleted && (
          <div className="absolute -top-2 -right-2 rounded-full bg-green-500 p-1 shadow-lg">
            <Trophy className="h-3 w-3 text-white" />
          </div>
        )}
      </div>

      <div className="mt-2 text-center">
        <p className="truncate px-1 text-sm font-medium">{milestone.name}</p>
        <p className="text-muted-foreground text-xs">{formatDistance(milestone.distance)} mi</p>
      </div>
    </div>
  );
}
