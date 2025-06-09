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
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';

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

interface Props {
  teams: Pagination<Team>;
  filters?: {
    searchTeam?: string;
    perPageTeam?: number | string;
  };
}

export default function FollowTeam({ teams, filters }: Props) {
  const [searchTeam, setSearchTeam] = useState(filters?.searchTeam || '');
  const [perPageTeam, setPerPageTeam] = useState(filters?.perPageTeam?.toString() || '5');

  // Debounced search + pagination update
  useEffect(() => {
    const timeout = setTimeout(() => {
      router.visit(route('follow'), {
        data: { searchTeam, perPageTeam },
        preserveScroll: true,
        preserveState: true,
        replace: true,
        only: ['teams'],
      });
    }, 500);
    return () => clearTimeout(timeout);
  }, [searchTeam, perPageTeam]);

  const handlePagination = (page: number) => {
    router.visit(route('follow'), {
      data: {
        searchTeam,
        perPageTeam,
        teamsPage: page,
      },
      preserveScroll: true,
      preserveState: true,
      replace: true,
      only: ['teams'],
    });
  };

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

          {teams.data.length === 0 ? (
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
        <div className="flex gap-2">
          <Button
            variant="outline-primary"
            onClick={() => handlePagination(teams.current_page - 1)}
            disabled={!teams.prev_page_url}
          >
            Previous
          </Button>
          <Button
            variant="outline-primary"
            onClick={() => handlePagination(teams.current_page + 1)}
            disabled={!teams.next_page_url}
          >
            Next
          </Button>
        </div>
      </CardFooter>
    </Card>
  );
}
