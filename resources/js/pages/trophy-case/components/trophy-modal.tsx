import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { ExternalLink, Play, Share2, Trophy } from 'lucide-react';
import { type Milestone } from './trophy-card';

interface TrophyModalProps {
  milestone: Milestone | null;
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
  isCompleted: boolean;
  showTeamView?: boolean;
  onShare?: (milestone: Milestone) => void;
}

export default function TrophyModal({ milestone, isOpen, onOpenChange, isCompleted, showTeamView = false, onShare }: TrophyModalProps) {
  if (!milestone) return null;

  const formatDistance = (distance: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 1,
    }).format(distance);
  };

  const handleShare = () => {
    if (onShare) {
      onShare(milestone);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Trophy className="h-5 w-5 text-amber-500" />
            {milestone.name}
          </DialogTitle>
          <DialogDescription>{formatDistance(milestone.distance)} mile milestone</DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          {/* Trophy Image */}
          <div className="flex justify-center">
            <div className="relative">
              <div className="bg-muted/10 aspect-square w-full overflow-hidden rounded-lg border">
                {milestone.logo_image_url ? (
                  <img
                    src={showTeamView ? milestone.team_logo_image_url || milestone.logo_image_url : milestone.logo_image_url}
                    alt={milestone.name}
                    className="h-full w-full object-contain"
                  />
                ) : (
                  <div className="flex h-full w-full items-center justify-center">
                    <Trophy className="text-muted-foreground/50 h-16 w-16" />
                  </div>
                )}
              </div>

              {isCompleted && (
                <Badge className="absolute -bottom-2 left-1/2 -translate-x-1/2 transform bg-green-500 hover:bg-green-600">
                  <Trophy className="mr-1 h-3 w-3" />
                  Earned
                </Badge>
              )}
            </div>
          </div>

          {/* Description */}
          {milestone.description && (
            <div className="text-center">
              <p className="text-muted-foreground text-sm">{milestone.description}</p>
            </div>
          )}

          {/* Video Link */}
          {milestone.video_url && (
            <Button variant="outline" className="w-full" asChild>
              <a href={milestone.video_url} target="_blank" rel="noopener noreferrer">
                <Play className="mr-2 h-4 w-4" />
                Watch Video
              </a>
            </Button>
          )}

          <Separator />

          {/* Action Buttons */}
          <div className="grid grid-cols-2 gap-2">
            <Button variant="outline" size="sm" onClick={handleShare} disabled={!isCompleted}>
              <Share2 className="mr-2 h-4 w-4" />
              Share
            </Button>

            <Button variant="outline" size="sm" asChild>
              <a href="#" target="_blank" rel="noopener noreferrer">
                <ExternalLink className="mr-2 h-4 w-4" />
                Bib
              </a>
            </Button>
          </div>

          {isCompleted && (
            <div className="pt-2 text-center">
              <p className="text-xs font-medium text-green-600 dark:text-green-400">🎉 Congratulations on earning this milestone!</p>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
