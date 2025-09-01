import PageContent from '@/components/atoms/page-content';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Trophy, Award, Star, Calendar, Target, Share2, ExternalLink, Play } from 'lucide-react';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useState, useMemo } from 'react';

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
  logo_image_url: string;
  team_logo_image_url: string;
  video_url?: string;
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
    
    const earned = trophyData.milestones.filter(m => m.is_completed).length;
    const total = trophyData.milestones.length;
    const percentage = total > 0 ? (earned / total) * 100 : 0;
    
    return { earned, total, percentage };
  }, [trophyData?.milestones]);

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
          <div className="flex flex-col items-center justify-center min-h-96">
            <Trophy className="h-24 w-24 text-muted-foreground/50 mb-4" />
            <h2 className="text-2xl font-semibold text-muted-foreground mb-2">
              {error || 'No Trophy Data Available'}
            </h2>
            <p className="text-muted-foreground/70 text-center max-w-md">
              {error ? 'Please check your event participation or try again later.' : 'Start participating in events to earn trophies and achievements!'}
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
          <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
              <div className="flex items-center gap-3 mb-2">
                <Trophy className="h-8 w-8 text-amber-500" />
                <h1 className="text-4xl font-bold">Trophy Case</h1>
              </div>
              <p className="text-xl text-muted-foreground">
                Your achievements in <span className="font-semibold text-foreground">{trophyData.event.name}</span>
              </p>
            </div>

            {/* Toggle Switch for Team View */}
            <Card className="w-fit">
              <CardContent className="flex items-center space-x-3 p-4">
                <Label htmlFor="team-view" className="text-sm font-medium">
                  Personal
                </Label>
                <Switch
                  id="team-view"
                  checked={showTeamView}
                  onCheckedChange={setShowTeamView}
                />
                <Label htmlFor="team-view" className="text-sm font-medium">
                  Team
                </Label>
              </CardContent>
            </Card>
          </div>

          {/* Stats Overview */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Card>
              <CardContent className="p-6">
                <div className="flex items-center gap-3">
                  <div className="p-2 rounded-full bg-amber-100 dark:bg-amber-900/20">
                    <Trophy className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">Trophies Earned</p>
                    <p className="text-2xl font-bold">{trophyStats.earned}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="p-6">
                <div className="flex items-center gap-3">
                  <div className="p-2 rounded-full bg-blue-100 dark:bg-blue-900/20">
                    <Target className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">Total Distance</p>
                    <p className="text-2xl font-bold">{formatDistance(trophyData.user_distance)} mi</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="p-6">
                <div className="flex items-center gap-3">
                  <div className="p-2 rounded-full bg-green-100 dark:bg-green-900/20">
                    <Award className="h-5 w-5 text-green-600 dark:text-green-400" />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">Completion</p>
                    <p className="text-2xl font-bold">{trophyStats.percentage.toFixed(0)}%</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="p-6">
                <div className="flex items-center gap-3">
                  <div className="p-2 rounded-full bg-purple-100 dark:bg-purple-900/20">
                    <Star className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">Progress</p>
                    <p className="text-2xl font-bold">{trophyStats.earned}/{trophyStats.total}</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Personal Achievements */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Award className="h-5 w-5 text-amber-500" />
                Personal Achievements
              </CardTitle>
              <CardDescription>
                Your best performances across different time periods
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {achievementItems.map((item, index) => {
                  const Icon = item.icon;
                  return (
                    <div key={index} className="relative overflow-hidden rounded-lg bg-gradient-to-br from-muted/50 to-muted/20 p-4">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                          <div className={`rounded-full bg-gradient-to-br ${item.gradient} p-2 text-white shadow-lg`}>
                            <Icon className="h-4 w-4" />
                          </div>
                          <div>
                            <h3 className="font-semibold text-sm">{item.title}</h3>
                            {item.date && (
                              <p className="text-xs text-muted-foreground">
                                {formatDate(item.date)}
                              </p>
                            )}
                          </div>
                        </div>
                        <div className="text-right">
                          <div className="text-lg font-bold">
                            {formatDistance(item.value)}
                          </div>
                          <Badge variant="secondary" className="text-xs">
                            miles
                          </Badge>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </CardContent>
          </Card>

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
                    {trophyStats.earned} of {trophyStats.total} badges earned ({trophyStats.percentage.toFixed(1)}%)
                  </CardDescription>
                </div>
                <Badge variant="outline" className="text-lg px-3 py-1">
                  {trophyStats.earned}/{trophyStats.total}
                </Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                {trophyData.milestones.map((milestone) => (
                  <Dialog key={milestone.id}>
                    <DialogTrigger asChild>
                      <div
                        className={`group cursor-pointer transition-all duration-300 hover:scale-105 ${
                          !milestone.is_completed ? 'grayscale opacity-30 hover:grayscale-0 hover:opacity-100' : ''
                        }`}
                        onClick={() => setSelectedTrophy(milestone)}
                      >
                        <div className="relative">
                          <div className="aspect-square rounded-lg overflow-hidden bg-muted/20 border-2 border-transparent group-hover:border-primary/20 transition-colors">
                            {milestone.logo_image_url ? (
                              <img
                                src={showTeamView ? (milestone.team_logo_image_url || milestone.logo_image_url) : milestone.logo_image_url}
                                alt={milestone.name}
                                className="w-full h-full object-cover"
                                loading="lazy"
                              />
                            ) : (
                              <div className="w-full h-full flex items-center justify-center">
                                <Trophy className="h-12 w-12 text-muted-foreground/50" />
                              </div>
                            )}
                          </div>
                          
                          {milestone.is_completed && (
                            <div className="absolute -top-2 -right-2 bg-green-500 rounded-full p-1 shadow-lg">
                              <Trophy className="h-3 w-3 text-white" />
                            </div>
                          )}
                        </div>
                        
                        <div className="mt-2 text-center">
                          <p className="text-xs font-medium truncate px-1">
                            {milestone.name}
                          </p>
                          <p className="text-xs text-muted-foreground">
                            {formatDistance(milestone.distance)} mi
                          </p>
                        </div>
                      </div>
                    </DialogTrigger>

                    {/* Trophy Modal */}
                    <DialogContent className="max-w-md">
                      <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                          <Trophy className="h-5 w-5 text-amber-500" />
                          {milestone.name}
                        </DialogTitle>
                        <DialogDescription>
                          {milestone.distance} mile milestone
                        </DialogDescription>
                      </DialogHeader>
                      
                      <div className="space-y-4">
                        {/* Trophy Image */}
                        <div className="flex justify-center">
                          <div className="relative">
                            <div className="aspect-square w-32 rounded-lg overflow-hidden bg-muted/10 border">
                              {milestone.logo_image_url ? (
                                <img
                                  src={showTeamView ? (milestone.team_logo_image_url || milestone.logo_image_url) : milestone.logo_image_url}
                                  alt={milestone.name}
                                  className="w-full h-full object-cover"
                                />
                              ) : (
                                <div className="w-full h-full flex items-center justify-center">
                                  <Trophy className="h-16 w-16 text-muted-foreground/50" />
                                </div>
                              )}
                            </div>
                            
                            {milestone.is_completed && (
                              <Badge className="absolute -bottom-2 left-1/2 transform -translate-x-1/2 bg-green-500 hover:bg-green-600">
                                <Trophy className="h-3 w-3 mr-1" />
                                Earned
                              </Badge>
                            )}
                          </div>
                        </div>

                        {/* Description */}
                        {milestone.description && (
                          <div className="text-center">
                            <p className="text-sm text-muted-foreground">
                              {milestone.description}
                            </p>
                          </div>
                        )}

                        {/* Video Link */}
                        {milestone.video_url && (
                          <Button variant="outline" className="w-full" asChild>
                            <a href={milestone.video_url} target="_blank" rel="noopener noreferrer">
                              <Play className="h-4 w-4 mr-2" />
                              Watch Video
                            </a>
                          </Button>
                        )}

                        <Separator />

                        {/* Action Buttons */}
                        <div className="grid grid-cols-2 gap-2">
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => shareAchievement(milestone)}
                            disabled={!milestone.is_completed}
                          >
                            <Share2 className="h-4 w-4 mr-2" />
                            Share
                          </Button>
                          
                          <Button variant="outline" size="sm" asChild>
                            <a href="#" target="_blank" rel="noopener noreferrer">
                              <ExternalLink className="h-4 w-4 mr-2" />
                              Bib
                            </a>
                          </Button>
                        </div>

                        {milestone.is_completed && (
                          <div className="text-center pt-2">
                            <p className="text-xs text-green-600 dark:text-green-400 font-medium">
                              🎉 Congratulations on earning this milestone!
                            </p>
                          </div>
                        )}
                      </div>
                    </DialogContent>
                  </Dialog>
                ))}
              </div>

              {trophyData.milestones.length === 0 && (
                <div className="text-center py-12">
                  <Trophy className="h-12 w-12 text-muted-foreground/50 mx-auto mb-4" />
                  <p className="text-muted-foreground">
                    No milestones available for this event yet.
                  </p>
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </PageContent>
    </AppLayout>
  );
}