import PageContent from '@/components/atoms/page-content';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { SocialShareButtons } from '@/components/ui/social-share-buttons';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Calendar, ExternalLink, Play, Star, Target, Trophy, User, Users } from 'lucide-react';
import { useMemo, useState } from 'react';

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

interface Milestone {
  id: number;
  name: string;
  description: string;
  distance: number;
  is_completed: boolean;
  is_team_completed: boolean;
  logo_image_url: string;
  team_logo_image_url: string;
  video_url?: string;
  earned_at?: string;
  team_earned_at?: string;
}

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

  const isMilestoneCompleted = function (milestone: Milestone): boolean {
    if (showTeamView) {
      return milestone.is_team_completed;
    }
    return milestone.is_completed;
  };

  const DisplayLogo = function (milestone: Milestone) {
    const logoUrl = showTeamView ? milestone.team_logo_image_url : milestone.logo_image_url;

    if (logoUrl) {
      return <img src={logoUrl} alt={milestone.name} className="h-full w-full object-cover" loading="lazy" />;
    }

    return (
      <div className="flex h-full w-full items-center justify-center">
        <Trophy className="text-muted-foreground/50 h-12 w-12" />
      </div>
    );
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
                <Trophy className="h-8 w-8 text-primary" />
                <h1 className="text-4xl font-bold">Trophy Case</h1>
              </div>
              <p className="text-muted-foreground text-xl">
                Your achievements in <span className="text-foreground font-semibold">{trophyData.event.name}</span>
              </p>
            </div>

            {/* Toggle Switch for Team View */}
            {trophyData.team && (
              <div className="flex gap-2">
                <Button variant={!showTeamView ? 'default' : 'secondary'} onClick={() => setShowTeamView(false)}>
                  <User /> You
                </Button>
                <Button variant={showTeamView ? 'default' : 'secondary'} onClick={() => setShowTeamView(true)}>
                  <Users /> Team
                </Button>
              </div>
            )}
          </div>

          {/* Trophy Grid */}
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="flex items-center gap-2">
                    {showTeamView ? 'Team ' : ''}Milestone Trophies
                  </CardTitle>
                  <CardDescription>
                    {trophyStats.earned} of {trophyStats.total} badges earned ({trophyStats.percentage.toFixed(1)}%)
                  </CardDescription>
                </div>
                <Badge variant="outline" className="px-3 py-1 text-md">
                  {trophyStats.earned}/{trophyStats.total}
                </Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 gap-6 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                {trophyData.milestones.map((milestone) => (
                  <Dialog key={milestone.id}>
                    <DialogTrigger asChild>
                      <div
                        className={`group cursor-pointer transition-all duration-300 hover:scale-105 ${
                          !isMilestoneCompleted(milestone) ? 'opacity-30 grayscale hover:opacity-100 hover:grayscale-0' : ''
                        }`}
                        onClick={() => setSelectedTrophy(milestone)}
                      >
                        {' '}
                        <div className="relative">
                          <div className="bg-muted/20 group-hover:border-primary/20 aspect-square overflow-hidden rounded-lg border-2 border-transparent transition-colors">
                            {DisplayLogo(milestone)}
                          </div>

                          {isMilestoneCompleted(milestone) && (
                            <div className="absolute -top-2 -right-2 rounded-full bg-green-500 p-1 shadow-lg">
                              <Trophy className="h-3 w-3 text-white" />
                            </div>
                          )}
                        </div>
                        <div className="mt-2 text-center">
                          <p className="truncate px-1 text-xs font-medium">{milestone.name}</p>
                          <p className="text-muted-foreground text-xs">{formatDistance(milestone.distance)} mi</p>
                          {isMilestoneCompleted(milestone) &&
                            (() => {
                              const earnedDate = showTeamView ? milestone.team_earned_at : milestone.earned_at;
                              return earnedDate ? <p className="text-muted-foreground text-xs">Earned {formatDate(earnedDate)}</p> : null;
                            })()}
                        </div>
                      </div>
                    </DialogTrigger>

                    {/* Trophy Modal */}
                    <DialogContent className="max-w-md">
                      <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                          <Trophy className="h-5 w-5 text-primary" />
                          {milestone.name}
                        </DialogTitle>
                        <DialogDescription>{milestone.distance} mile milestone</DialogDescription>
                      </DialogHeader>

                      <div className="space-y-10">
                        {/* Trophy Image */}
                        <div className="flex justify-center">
                          <div className="relative">
                            <div className="bg-muted/10 aspect-square w-80 overflow-hidden rounded-lg border">{DisplayLogo(milestone)}</div>

                            {isMilestoneCompleted(milestone) && (
                              <Badge className="absolute -bottom-2 left-1/2 -translate-x-1/2 transform bg-green-500">
                                <Trophy className="mr-1 size-3" />
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

                        {/* Video */}
                        {milestone.video_url &&
                          (() => {
                            const vimeoMatch = milestone.video_url.match(/vimeo\.com\/(\d+)/);
                            if (vimeoMatch) {
                              return (
                                <div className="aspect-video w-full overflow-hidden rounded-lg">
                                  <iframe
                                    src={`https://player.vimeo.com/video/${vimeoMatch[1]}`}
                                    className="h-full w-full"
                                    frameBorder="0"
                                    allow="autoplay; fullscreen; picture-in-picture"
                                    allowFullScreen
                                    title={milestone.name}
                                  />
                                </div>
                              );
                            }
                            return (
                              <Button variant="outline" className="w-full" asChild>
                                <a href={milestone.video_url} target="_blank" rel="noopener noreferrer">
                                  <Play className="mr-2 h-4 w-4" />
                                  Watch Video
                                </a>
                              </Button>
                            );
                          })()}

                        <Separator />

                        {/* Social Share Section */}
                        {isMilestoneCompleted(milestone) && (
                          <div className="space-y-3">
                            <div className="text-center">
                              <h4 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Share Your Achievement</h4>
                              <div className="flex justify-center">
                                <SocialShareButtons
                                  title={
                                    showTeamView
                                      ? `My team and I just reached ${milestone.name} in our ${trophyData.event.name} journey. That's ${Math.floor(milestone.distance)} miles y'all!`
                                      : `I just reached ${milestone.name} in ${trophyData.event.name} journey. That's ${Math.floor(milestone.distance)} miles y'all!`
                                  }
                                  url={route('shared.milestone', {
                                    milestoneId: milestone.id,
                                    ...(showTeamView && { team: true })
                                  })}
                                  imageUrl={showTeamView ? milestone.team_logo_image_url : milestone.logo_image_url}
                                />
                              </div>
                            </div>

                            {/* Bib Button */}
                            <Button variant="outline" size="sm" asChild className="w-full">
                              <a href="#" target="_blank" rel="noopener noreferrer">
                                <ExternalLink className="mr-2 h-4 w-4" />
                                View Custom Bib
                              </a>
                            </Button>
                          </div>
                        )}

                        {!isMilestoneCompleted(milestone) && (
                          <div className="pt-2 text-center">
                            <p className="text-muted-foreground text-sm">Complete this milestone to unlock sharing!</p>
                          </div>
                        )}

                        {isMilestoneCompleted(milestone) && (
                          <div className="pt-2 text-center">
                            <p className="text-xs font-medium text-green-600 dark:text-green-400">🎉 Congratulations on earning this milestone!</p>
                          </div>
                        )}
                      </div>
                    </DialogContent>
                  </Dialog>
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
      </PageContent>
    </AppLayout>
  );
}
