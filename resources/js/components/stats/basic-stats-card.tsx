import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { StatCardSkeleton } from '@/components/ui/skeleton-components';
import { Target } from 'lucide-react';
import { useEffect, useState } from 'react';

interface BasicStats {
  total_miles: number;
}

export default function BasicStatsCard() {
  const [stats, setStats] = useState<BasicStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchBasicStats = async () => {
      try {
        setLoading(true);
        // const response = await axios.get(route('api.stats.basic'));
        // setStats(response.data.stats);
      } catch (err) {
        setError('Failed to load basic statistics');
        console.error('Error fetching basic stats:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchBasicStats();
  }, []);

  if (loading) {
    return <StatCardSkeleton />;
  }

  if (error) {
    return (
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Total Miles</CardTitle>
          <Target className="text-muted-foreground h-4 w-4" />
        </CardHeader>
        <CardContent>
          <div className="text-sm text-red-500">{error}</div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium">Total Miles</CardTitle>
        <Target className="text-muted-foreground h-4 w-4" />
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-bold">{stats?.total_miles.toFixed(2) || '0.00'}</div>
        <p className="text-muted-foreground text-xs">Miles completed in this event</p>
      </CardContent>
    </Card>
  );
}
