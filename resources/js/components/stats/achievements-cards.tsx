import { useState, useEffect } from 'react';
import axios from 'axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { StatCardSkeleton } from '@/components/ui/skeleton-components';
import { TrendingUp, Calendar, Trophy } from 'lucide-react';

interface Achievement {
  date?: string;
  miles?: number;
  yearweek?: string;
  year?: number;
  month?: number;
}

interface Achievements {
  best_day: Achievement | null;
  best_week: Achievement | null;
  best_month: Achievement | null;
}

export default function AchievementsCards() {
  const [achievements, setAchievements] = useState<Achievements | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchAchievements = async () => {
      try {
        setLoading(true);
        const response = await axios.get(route('api.stats.achievements'));
        setAchievements(response.data.achievements);
      } catch (err) {
        setError('Failed to load achievements');
        console.error('Error fetching achievements:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchAchievements();
  }, []);

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  const formatMonth = (year: number, month: number) => {
    return new Date(year, month - 1).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long'
    });
  };

  if (loading) {
    return (
      <>
        <StatCardSkeleton />
        <StatCardSkeleton />
        <StatCardSkeleton />
      </>
    );
  }

  if (error) {
    return (
      <Card className="col-span-3">
        <CardContent className="p-6">
          <div className="text-red-500 text-center">{error}</div>
        </CardContent>
      </Card>
    );
  }

  return (
    <>
      {/* Best Day */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Best Day</CardTitle>
          <TrendingUp className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">
            {achievements?.best_day ? achievements.best_day.miles?.toFixed(2) : '0.00'}
          </div>
          <p className="text-xs text-muted-foreground">
            {achievements?.best_day?.date ? formatDate(achievements.best_day.date) : 'No data available'}
          </p>
        </CardContent>
      </Card>

      {/* Best Week */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Best Week</CardTitle>
          <Calendar className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">
            {achievements?.best_week ? achievements.best_week.miles?.toFixed(2) : '0.00'}
          </div>
          <p className="text-xs text-muted-foreground">
            {achievements?.best_week?.yearweek ? `Week ${achievements.best_week.yearweek}` : 'No data available'}
          </p>
        </CardContent>
      </Card>

      {/* Best Month */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Best Month</CardTitle>
          <Trophy className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">
            {achievements?.best_month ? achievements.best_month.miles?.toFixed(2) : '0.00'}
          </div>
          <p className="text-xs text-muted-foreground">
            {achievements?.best_month?.year && achievements?.best_month?.month 
              ? formatMonth(achievements.best_month.year, achievements.best_month.month) 
              : 'No data available'}
          </p>
        </CardContent>
      </Card>
    </>
  );
}
