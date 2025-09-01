import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Calendar, Trophy, Target, TrendingUp } from 'lucide-react';
import { useMemo, useState, useEffect } from 'react';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import axios from 'axios';

interface PersonalBest {
  accomplishment: number;
  date: string;
  achievement: string;
}

interface AchievementData {
  best_day: PersonalBest;
  best_week: PersonalBest;
  best_month: PersonalBest;
}

interface PersonalBestsProps {
  className?: string;
}

export default function PersonalBests({
  className = '',
}: PersonalBestsProps) {
  const { auth } = usePage<SharedData>().props;
  const [loading, setLoading] = useState(true);
  const [achievementData, setAchievementData] = useState<AchievementData | null>(null);

  useEffect(() => {
    const fetchAchievements = async () => {
      try {
        const response = await axios.get(route('web.user.achievements'), {
          params: {
            event_id: auth.preferred_event.id,
          },
        });
        setAchievementData(response.data.data.achievement);
        setLoading(false);
      } catch (err) {
        console.error('Error fetching achievements:', err);
        setLoading(false);
      }
    };

    fetchAchievements();
  }, []);

  const formatDistance = (distance: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
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

  const personalBestItems = useMemo(() => [
    {
      title: 'Best Day',
      icon: Target,
      data: achievementData?.best_day?.accomplishment ? {
        distance: achievementData.best_day.accomplishment,
        date: achievementData.best_day.date,
      } : null,
      gradient: 'from-emerald-500 to-teal-600',
      bgGradient: 'from-emerald-50 to-teal-50',
      darkBgGradient: 'dark:from-emerald-950/20 dark:to-teal-950/20',
    },
    {
      title: 'Best Week',
      icon: Calendar,
      data: achievementData?.best_week?.accomplishment ? {
        distance: achievementData.best_week.accomplishment,
        date: achievementData.best_week.date,
      } : null,
      gradient: 'from-blue-500 to-cyan-600',
      bgGradient: 'from-blue-50 to-cyan-50',
      darkBgGradient: 'dark:from-blue-950/20 dark:to-cyan-950/20',
    },
    {
      title: 'Best Month',
      icon: TrendingUp,
      data: achievementData?.best_month?.accomplishment ? {
        distance: achievementData.best_month.accomplishment,
        date: achievementData.best_month.date,
      } : null,
      gradient: 'from-purple-500 to-pink-600',
      bgGradient: 'from-purple-50 to-pink-50',
      darkBgGradient: 'dark:from-purple-950/20 dark:to-pink-950/20',
    },
  ], [achievementData]);

  if (loading) {
    return (
      <Card className={`${className}`}>
        <CardHeader className="pb-3">
          <Skeleton className="h-6 w-48 mb-2" />
          <Skeleton className="h-4 w-full" />
        </CardHeader>
        <CardContent className="space-y-3">
          {[1, 2, 3].map((i) => (
            <div key={i} className="flex items-center justify-between p-3 rounded-lg border">
              <div className="flex items-center gap-2">
                <Skeleton className="h-6 w-6 rounded-full" />
                <div>
                  <Skeleton className="h-4 w-20 mb-1" />
                  <Skeleton className="h-3 w-16" />
                </div>
              </div>
              <div className="text-right">
                <Skeleton className="h-5 w-12 mb-1" />
                <Skeleton className="h-4 w-8" />
              </div>
            </div>
          ))}
          <div className="mt-4 p-3 rounded-lg border">
            <Skeleton className="h-3 w-32 mb-2" />
            <div className="grid grid-cols-3 gap-2">
              <Skeleton className="h-8 w-full" />
              <Skeleton className="h-8 w-full" />
              <Skeleton className="h-8 w-full" />
            </div>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className={`${className}`}>
      <CardHeader className="pb-3">
        <div className="flex items-center gap-2">
          <Trophy className="h-5 w-5 text-amber-500" />
          <CardTitle className="text-lg">Personal Bests</CardTitle>
        </div>
        <CardDescription className="text-sm">
          Your best performances across different time periods
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-3">
        {personalBestItems.map((item, index) => {
          const Icon = item.icon;

          return (
            <div
              key={index}
              className={`relative overflow-hidden rounded-lg bg-gradient-to-br ${item.bgGradient} ${item.darkBgGradient} p-3 transition-all duration-200 hover:scale-[1.02]`}
            >
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <div className={`rounded-full bg-gradient-to-br ${item.gradient} p-1.5 text-white shadow-lg`}>
                    <Icon className="h-3 w-3" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-md text-gray-900 dark:text-gray-100">
                      {item.title}
                    </h3>
                    {item.data ? (
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        {formatDate(item.data.date)}
                      </p>
                    ) : (
                      <p className="text-xs text-gray-500 dark:text-gray-500">
                        No data yet
                      </p>
                    )}
                  </div>
                </div>

                <div className="text-right flex flex-col">
                  {item.data ? (
                    <>
                      <div className="text-lg leading-none font-bold text-gray-900 dark:text-gray-100">
                        {formatDistance(item.data.distance)}
                      </div>
                      <span className="text-xs py-0 px-1">
                        miles
                      </span>
                    </>
                  ) : (
                    <div className="text-sm font-medium text-gray-400 dark:text-gray-600">
                      --
                    </div>
                  )}
                </div>
              </div>

              {/* Decorative gradient overlay */}
              <div className={`absolute -bottom-1 -right-4 h-16 w-16 rounded-full bg-gradient-to-br ${item.gradient} opacity-10`} />
            </div>
          );
        })}
      </CardContent>
    </Card>
  );
}
