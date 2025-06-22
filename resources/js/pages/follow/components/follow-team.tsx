import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Search } from 'lucide-react';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useState, useEffect } from 'react';
import axios from 'axios';

interface Team {
  id: number;
  name: string;
  public_profile: boolean;
  total_members?: number;
  total_miles?: number;
  membership_status: string | null;
}

interface Pagination<T> {
  data: T[];
  current_page: number;
  next_page_url: string | null;
  prev_page_url: string | null;
}

export default function FollowTeam() {
  const [teams, setTeams] = useState<Pagination<Team> | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchTeam, setSearchTeam] = useState('');
  const [perPageTeam, setPerPageTeam] = useState('5');
  const [currentPage, setCurrentPage] = useState(1);

  const fetchTeams = async (page: number = currentPage) => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (searchTeam) params.append('searchTeam', searchTeam);
      if (perPageTeam) params.append('perPageTeam', perPageTeam);
      params.append('page', page.toString());

      const response = await axios.get(route('api.follow.available-teams') + '?' + params.toString());
      setTeams(response.data.teams);
      setCurrentPage(page);
    } catch (err) {
      setError('Failed to load available teams');
      console.error('Error fetching available teams:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTeams(1); // Reset to page 1 on initial load
  }, []);

  // Debounced search + pagination update - reset to page 1 when search changes
  useEffect(() => {
    const timeout = setTimeout(() => {
      setCurrentPage(1);
      fetchTeams(1);
    }, 500);
    return () => clearTimeout(timeout);
  }, [searchTeam, perPageTeam]);

  const handlePagination = (page: number) => {
    fetchTeams(page);
  };

  // Skeleton component for individual team rows
  const TeamRowSkeleton = () => (
    <div className="grid grid-cols-5 items-center px-4 py-3 border-b text-sm">
      <div>
        <Skeleton className="h-4 w-32" />
      </div>
      <div>
        <Skeleton className="h-4 w-16" />
      </div>
      <div>
        <Skeleton className="h-4 w-8" />
      </div>
      <div>
        <Skeleton className="h-4 w-12" />
      </div>
      <div className="text-right">
        <Skeleton className="h-8 w-16" />
      </div>
    </div>
  );

  return (
    <Card>
      <CardHeader>
        <CardTitle>Choose Teams To Follow</CardTitle>
        <CardDescription>
          If you want to follow a team, browse below and follow. If a team has a private profile, you must be approved to follow them. Because, <a href='https://open.spotify.com/user/i8jbm6uxgffk9f29pvtiwvq21/playlist/6MfRx31RscWGG3gUQ1R3Wm' target="_blank" rel="noreferrer" className="underline text-blue-600">privacy/GDPR</a>...
        </CardDescription>
      </CardHeader>

      <CardContent className="space-y-6">
        {/* Filters */}
        <div className="flex min-h-12 gap-5">
          <div className="relative w-full">
            <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
            <Input
              type="search"
              placeholder="Search..."
              className="h-full pl-10"
              value={searchTeam}
              onChange={(e) => setSearchTeam(e.target.value)}
            />
          </div>
          <div className="relative w-full max-w-50">
            <Select
              value={perPageTeam}
              onValueChange={setPerPageTeam}
            >
              <SelectTrigger className="h-full">
                <SelectValue placeholder="Records per page" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="5">5 per page</SelectItem>
                <SelectItem value="10">10 per page</SelectItem>
                <SelectItem value="25">25 per page</SelectItem>
                <SelectItem value="50">50 per page</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        {/* Team Listing */}
        <div className="border rounded-md">
          <div className="grid grid-cols-5 px-4 py-2 border-b bg-muted text-muted-foreground text-sm font-medium">
            <div>Name</div>
            <div>Privacy</div>
            <div>Members</div>
            <div>Mileage</div>
            <div className="text-right">Action</div>
          </div>

          {loading ? (
            // Show skeleton rows while loading
            <>
              <TeamRowSkeleton />
              <TeamRowSkeleton />
              <TeamRowSkeleton />
              <TeamRowSkeleton />
              <TeamRowSkeleton />
            </>
          ) : error ? (
            <div className="p-8 text-center text-red-500">{error}</div>
          ) : !teams || teams.data.length === 0 ? (
            <div className="p-4 text-center text-muted-foreground">No teams found.</div>
          ) : (
            teams.data.map((team) => (
              <div
                key={team.id}
                className="grid grid-cols-5 items-center px-4 py-3 border-b text-sm"
              >
                <div>{team.name}</div>
                <div>{team.public_profile ? 'Public' : 'Private'}</div>
                <div>{team.total_members ?? '-'}</div>
                <div>{team.total_miles?.toFixed(1) ?? '-'}</div>
                <div className="text-right">
                  {/* Replace with real follow logic */}
                  <Button variant="default" size="sm">Follow</Button>
                </div>
              </div>
            ))
          )}
        </div>
      </CardContent>

      <CardFooter className="justify-end">
        {loading ? (
          <div className="flex gap-2">
            <Skeleton className="h-10 w-20" />
            <Skeleton className="h-10 w-16" />
          </div>
        ) : (
          <div className="flex gap-2">
            <Button
              variant="outline"
              onClick={() => handlePagination((teams?.current_page || 1) - 1)}
              disabled={!teams?.prev_page_url}
            >
              Previous
            </Button>
            <Button
              variant="outline"
              onClick={() => handlePagination((teams?.current_page || 1) + 1)}
              disabled={!teams?.next_page_url}
            >
              Next
            </Button>
          </div>
        )}
      </CardFooter>
    </Card>
  );
}
