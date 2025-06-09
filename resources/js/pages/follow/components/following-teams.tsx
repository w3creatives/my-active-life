import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';

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

interface Props {
  teams: {
    data: {
      id: number;
      team: Team;
      statistics: TeamStatistics;
    }[];
  };
}

export default function FollowingTeams({ teams }: Props) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Teams I Follow</CardTitle>
        <CardDescription>
          Here's a list of teams you’re currently following.
        </CardDescription>
      </CardHeader>

      <CardContent className="space-y-4">
        {teams.data.length === 0 ? (
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
                <div className="flex items-center gap-3">
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
                  <Button variant="danger" size="sm">Unfollow</Button>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
