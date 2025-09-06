import PageContent from '@/components/atoms/page-content';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Award, Calendar, ExternalLink, Play, Share2, Star, Target, Trophy } from 'lucide-react';
import { useMemo, useState } from 'react';
import TrophyCard, { type Milestone } from '@/pages/trophy-case/components/trophy-card';
import TrophyModal from '@/pages/trophy-case/components/trophy-modal';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: route('dashboard'),
  },
  {
    title: 'Trophy Case',
    href: route('trophy-case'),
  },
];


interface Achievement {
  best_day: {
    accomplishment: number;
    date: string;
    achievement: string;
  };
  best_week: {
    accomplishment: number;
    date: string;
    achievement: string;
  };
  best_month: {
    accomplishment: number;
    date: string;
    achievement: string;
  };
}

interface TrophyData {
  event: {
    id: number;
    name: string;
    event_type: string;
  };
  milestones: Milestone[];
  achievements: Achievement;
  total_distance: number;
  user_distance: number;
}

interface TrophyCaseProps {
  trophyData?: TrophyData;
  error?: string;
}

export default function TrophyCase({ trophyData, error }: TrophyCaseProps) {
  const { auth } = usePage<SharedData>().props;
  const [showTeamView, setShowTeamView] = useState(false);
  const [selectedTrophy, setSelectedTrophy] = useState<Milestone | null>(null);
  const [isModalOpen, setIsModalOpen] = useState(false);

  const isMilestoneCompleted = function (milestone: Milestone): boolean {
    if (showTeamView) {
      return milestone.is_team_completed;
    }
    return milestone.is_completed;
  };
  const formatDistance = (distance: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 1,
    }).format(distance);
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  };

  const trophyStats = useMemo(() => {
    if (!trophyData?.milestones) return { earned: 0, total: 0, percentage: 0 };

    const earned = trophyData.milestones.filter((m) => (showTeamView ? m.is_team_completed : m.is_completed)).length;
    const total = trophyData.milestones.length;
    const percentage = total > 0 ? (earned / total) * 100 : 0;
    return { earned, total, percentage };
  }, [trophyData?.milestones, showTeamView]);

  const achievementItems = useMemo(() => {
    if (!trophyData?.achievements) return [];

    return [
      {
        title: 'Best Day',
        icon: Target,
        value: trophyData.achievements.best_day?.accomplishment || 0,
        date: trophyData.achievements.best_day?.date,
        gradient: 'from-emerald-500 to-teal-600',
      },
      {
        title: 'Best Week',
        icon: Calendar,
        value: trophyData.achievements.best_week?.accomplishment || 0,
        date: trophyData.achievements.best_week?.date,
        gradient: 'from-blue-500 to-cyan-600',
      },
      {
        title: 'Best Month',
        icon: Star,
        value: trophyData.achievements.best_month?.accomplishment || 0,
        date: trophyData.achievements.best_month?.date,
        gradient: 'from-purple-500 to-pink-600',
      },
    ];
  }, [trophyData?.achievements]);

  const shareAchievement = (milestone: Milestone) => {
    const text = `I just earned the ${milestone.name} milestone in ${trophyData?.event.name}! 🏆`;
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

  if (error || !trophyData) {
    return (
      <AppLayout breadcrumbs={breadcrumbs}>
        <Head title="Trophy Case" />
        <PageContent>
          <div className="flex min-h-96 flex-col items-center justify-center">
            <Trophy className="text-muted-foreground/50 mb-4 h-24 w-24" />
            <h2 className="text-muted-foreground mb-2 text-2xl font-semibold">{error || 'No Trophy Data Available'}</h2>
            <p className="text-muted-foreground/70 max-w-md text-center">
              {error
                ? 'Please check your event participation or try again later.'
                : 'Start participating in events to earn trophies and achievements!'}
            </p>
          </div>
        </PageContent>
      </AppLayout>
    );
  }

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Trophy Case" />
      <PageContent>
        <div className="space-y-8">
          {/* Header Section */}
          <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <div className="mb-2 flex items-center gap-3">
                <Trophy className="h-8 w-8 text-amber-500" />
                <h1 className="text-4xl font-bold">Trophy Case</h1>
              </div>
              <p className="text-muted-foreground text-xl">
                Your achievements in <span className="text-foreground font-semibold">{trophyData.event.name}</span>
              </p>
            </div>

            {/* Toggle Switch for Team View */}
            {trophyData.team && (
              <Card className="w-fit">
                <CardContent className="flex items-center space-x-3 p-4">
                  <Label htmlFor="team-view" className="text-sm font-medium">
                    Personal
                  </Label>
                  <Switch id="team-view" checked={showTeamView} onCheckedChange={setShowTeamView} />
                  <Label htmlFor="team-view" className="text-sm font-medium">
                    Team
                  </Label>
                </CardContent>
              </Card>
            )}
          </div>

          {/* Trophy Grid */}
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="flex items-center gap-2">
                    <Trophy className="h-5 w-5 text-amber-500" />
                    {showTeamView ? 'Team ' : ''}Milestone Trophies
                  </CardTitle>
                  <CardDescription>
                    {trophyStats.earned} of {trophyStats.total} bibs earned ({trophyStats.percentage.toFixed(1)}%)
                  </CardDescription>
                </div>
                <Badge variant="outline" className="px-3 py-1 text-lg">
                  {trophyStats.earned}/{trophyStats.total}
                </Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 gap-6 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                {trophyData.milestones.map((milestone) => (
                  <TrophyCard
                    key={milestone.id}
                    milestone={milestone}
                    isCompleted={isMilestoneCompleted(milestone)}
                    showTeamView={showTeamView}
                    onClick={() => {
                      setSelectedTrophy(milestone);
                      setIsModalOpen(true);
                    }}
                  />
                ))}
              </div>

              {trophyData.milestones.length === 0 && (
                <div className="py-12 text-center">
                  <Trophy className="text-muted-foreground/50 mx-auto mb-4 h-12 w-12" />
                  <p className="text-muted-foreground">No milestones available for this event yet.</p>
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        <TrophyModal
          milestone={selectedTrophy}
          isOpen={isModalOpen}
          onOpenChange={setIsModalOpen}
          isCompleted={selectedTrophy ? isMilestoneCompleted(selectedTrophy) : false}
          showTeamView={showTeamView}
          onShare={shareAchievement}
        />
      </PageContent>
    </AppLayout>
  );
}
