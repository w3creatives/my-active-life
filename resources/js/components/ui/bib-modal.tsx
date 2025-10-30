import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { ExternalLink, Share2, Trophy } from 'lucide-react';
import { type Milestone } from '@/components/ui/calendar';

interface BibModalProps {
  milestone: Milestone | null;
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
  showTeamView?: boolean;
  onShare?: (milestone: Milestone) => void;
}

export default function BibModal({
  milestone,
  isOpen,
  onOpenChange,
  showTeamView = false,
  onShare
}: BibModalProps) {
  if (!milestone) return null;

  const formatDistance = (distance: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 1,
    }).format(distance);
  };


  // Get the appropriate image URL based on team view
  const getImageUrl = () => {
    if (showTeamView) {
      return milestone.calendar_team_logo_url ||
             milestone.calendar_logo_url ||
             milestone.team_bib_image_url ||
             milestone.bib_image_url;
    }
    return milestone.calendar_logo_url || milestone.bib_image_url;
  };

  const shareAchievement = (milestone: Milestone) => {
    const text = `I just earned the ${milestone.name} milestone! 🏆`;
    const url = window.location.href;

    if (navigator.share) {
      navigator.share({
        title: milestone.name,
        text,
        url,
      });
    } else {
      // Fallback to copying to clipboard
      navigator.clipboard.writeText(`${text} ${url}`);
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
          {/* Bib Image */}
          <div className="flex justify-center">
            <div className="relative">
              <div className="bg-muted/10 aspect-square w-full overflow-hidden rounded-lg border">
                {getImageUrl() ? (
                  <img
                    src={getImageUrl()}
                    alt={milestone.name}
                    className="h-full w-full object-contain" onError={(e) => {e.currentTarget.src="/images/default-placeholder.png";}}
                  />
                ) : (
                  <div className="flex h-full w-full items-center justify-center">
                    <Trophy className="text-muted-foreground/50 h-16 w-16" />
                  </div>
                )}
              </div>

                {milestone.is_completed && <Badge className="absolute -bottom-2 left-1/2 -translate-x-1/2 transform bg-green-500 hover:bg-green-600">
                <Trophy className="mr-1 h-3 w-3" />
                Earned
              </Badge>}
            </div>
          </div>

          {/* Description */}
          {milestone.description && (
            <div className="text-center">
              <p className="text-muted-foreground text-sm">{milestone.description}</p>
            </div>
          )}

          <Separator />

          {/* Action Buttons */}
          <div className="grid grid-cols-2 gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => shareAchievement(milestone)}
            >
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

            {milestone.is_completed &&  <div className="pt-2 text-center">
            <p className="text-xs font-medium text-green-600 dark:text-green-400">🎉 Congratulations on earning this milestone!</p>
          </div>}
        </div>
      </DialogContent>
    </Dialog>
  );
}
