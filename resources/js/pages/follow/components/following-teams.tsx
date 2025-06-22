import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { useState, useEffect } from 'react';
import axios from 'axios';

interface TeamStatistics {
  distance_total: number;
  distance_completed: number;
  distance_remaining: number;
  progress_percentage: number;
}

interface Team {
  id: number;
  name: string;
  logo_url?: string;
  statistics: TeamStatistics;
}

interface TeamsData {
  data: {
    id: number;
    team: Team;
    statistics: TeamStatistics;
  }[];
}

export default function FollowingTeams() {
  const [teams, setTeams] = useState<TeamsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [unfollowingId, setUnfollowingId] = useState<number | null>(null);

  const fetchTeamFollowings = async () => {
    try {
      setLoading(true);
      const response = await axios.get(route('api.follow.team-followings'));
      setTeams(response.data.teamFollowings);
    } catch (err) {
      setError('Failed to load following teams');
      console.error('Error fetching team followings:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTeamFollowings();
  }, []);

  function handleUnfollow(teamId: number, teamName: string) {
    // Prevent multiple clicks
    if (unfollowingId === teamId) {
      return;
    }

    setUnfollowingId(teamId);
    router.post(
      '/unfollow/team',
      { team_id: teamId, event_id: 64 },
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success(`You have successfully unfollowed ${teamName}.`);
          // Refresh the data after successful unfollow
          fetchTeamFollowings();
        },
        onError: (errors) => {
          console.error('Unfollow error:', errors);
          // Check if there's a specific error message
          const errorMessage = errors.message || errors.error || `Failed to unfollow ${teamName}. Please try again.`;
          toast.error(errorMessage);
        },
        onFinish: () => {
          setUnfollowingId(null);
        },
      }
    );
  }

  // Skeleton component for individual team rows
  const TeamSkeleton = () => (
    <div className="flex items-center justify-between border-b pb-4 last:border-b-0">
      {/* Avatar + Team Name */}
      <div className="flex items-center gap-3 min-w-36">
        <Skeleton className="size-10 rounded-full" />
        <Skeleton className="h-4 w-20" />
      </div>

      {/* Progress Bar */}
      <div className="flex-1 mx-6">
        <Skeleton className="w-full h-2 rounded-full" />
      </div>

      {/* Miles & Unfollow */}
      <div className="flex items-center gap-4">
        <Skeleton className="h-4 w-16" />
        <Skeleton className="h-8 w-20" />
      </div>
    </div>
  );

  return (
    <Card>
      <CardHeader>
        <CardTitle>Teams I Follow</CardTitle>
        <CardDescription>
          Here's a list of teams you’re currently following.
        </CardDescription>
      </CardHeader>

      <CardContent className="space-y-4">
        {loading ? (
          // Show skeleton rows while loading
          <div className="space-y-4">
            <TeamSkeleton />
            <TeamSkeleton />
            <TeamSkeleton />
          </div>
        ) : error ? (
          <div className="text-red-500 text-center">{error}</div>
        ) : !teams || teams.data.length === 0 ? (
          <div className="text-muted-foreground text-center">
            You are not following any teams yet.
          </div>
        ) : (
          <div className="space-y-4">
            {teams.data.map(({ id, team, statistics }) => (
              <div
                key={id}
                className="flex items-center justify-between border-b pb-4 last:border-b-0"
              >
                {/* Avatar + Team Name */}
                <div className="flex items-center gap-3 min-w-36">
                  <div
                    className="size-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 uppercase text-sm font-medium"
                    aria-label={`Initial of ${team.name}`}
                  >
                    {team.name?.charAt(0) || 'T'}
                  </div>
                  <div className="font-medium">{team.name}</div>
                </div>

                {/* Progress Bar */}
                <div className="flex-1 mx-6">
                  <div className="w-full bg-gray-200 rounded-full h-2">
                    <div
                      className="bg-primary h-2 rounded-full"
                      style={{ width: `${Math.min(statistics.progress_percentage, 100)}%` }}
                    ></div>
                  </div>
                </div>

                {/* Miles & Unfollow */}
                <div className="flex items-center gap-4">
                  <div className="text-sm font-medium text-muted-foreground whitespace-nowrap">
                    {statistics.distance_completed.toFixed(1)} mi
                  </div>
                  <Button
                    variant="danger"
                    size="sm"
                    onClick={() => handleUnfollow(id, team.name)}
                    disabled={unfollowingId === id}
                  >
                    {unfollowingId === id ? 'Unfollowing' : 'Unfollow'}
                  </Button>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
