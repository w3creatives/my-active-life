import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import type { SharedData } from '@/types';
import axios from 'axios';
import { router, usePage } from '@inertiajs/react';
import { Lock, Search, Unlock } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface User {
  id: number;
  display_name: string;
  first_name: string;
  last_name: string;
  city: string;
  state: string;
  public_profile: boolean;
  following_status_text: string;
  following_status: string;
}

interface Pagination<T> {
  data: T[];
  current_page: number;
  next_page_url: string | null;
  prev_page_url: string | null;
}

interface TeamToJoinProps {
  onRequestChange?: () => void;
}

export default function TeamToJoin({ onRequestChange }: TeamToJoinProps) {
  const { auth } = usePage<SharedData>().props;

  const [teams, setTeams] = useState<Pagination<User> | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchTeam, setSearchTeam] = useState('');
  const [perPage, setperPage] = useState('5');
  const [currentPage, setCurrentPage] = useState(1);
  const [teamJoinActionProcessing, setTeamJoinActionProcessing] = useState(false);
  const [hasPendingRequest, setHasPendingRequest] = useState(false);
  const [pendingRequestId, setPendingRequestId] = useState<number | null>(null);
  const [pendingRequestTeamId, setPendingRequestTeamId] = useState<number | null>(null);
  const [hoveredTeamId, setHoveredTeamId] = useState<number | null>(null);

  const fetchTeams = async (page: number = currentPage) => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (searchTeam) params.append('searchTeam', searchTeam);
      if (perPage) params.append('perPage', perPage);
      params.append('page', page.toString());

      const response = await axios.get(route('teams.team-to-join') + '?' + params.toString());
      setTeams(response.data.teams);
      setCurrentPage(page);
    } catch (err) {
      setError('Failed to load available team members');
      console.error('Error fetching available team members:', err);
    } finally {
      setLoading(false);
    }
  };

  const checkPendingRequests = async () => {
    try {
      const response = await axios.get(route('teams.user-join-requests'));
      const hasPending = response.data.data && response.data.data.length > 0;
      setHasPendingRequest(hasPending);

      // Store the pending request details
      if (hasPending && response.data.data.length > 0) {
        const pendingRequest = response.data.data[0];
        setPendingRequestId(pendingRequest.id);
        setPendingRequestTeamId(pendingRequest.team_id);
      } else {
        setPendingRequestId(null);
        setPendingRequestTeamId(null);
      }
    } catch (err) {
      console.error('Error checking pending requests:', err);
    }
  };

  useEffect(() => {
    fetchTeams(1); // Reset to page 1 on initial load
    checkPendingRequests(); // Check if user has any pending requests
  }, []);

  // Debounced search trigger - reset to page 1 when search changes
  useEffect(() => {
    const timeout = setTimeout(() => {
      setCurrentPage(1);
      fetchTeams(1);
    }, 500);
    return () => clearTimeout(timeout);
  }, [searchTeam, perPage]);

  const handlePagination = (page: number) => {
    fetchTeams(page);
  };

  const handleTeamJoinAction = (team: object) => {
    // If this is the team with pending request, cancel it
    if (pendingRequestTeamId === team.id && pendingRequestId) {
      handleCancelRequest(team);
      return;
    }

    // Otherwise, send a new join request
    setTeamJoinActionProcessing(true);
    router.post(route('teams.team-to-join-request'), {
      event_id: auth.preferred_event.id,
      team_id: team.id,
    }, {
      onSuccess: () => {
        setTeamJoinActionProcessing(false);
        // Refresh the teams list and check pending requests
        fetchTeams(currentPage);
        checkPendingRequests();
        // Notify parent to refresh other components
        onRequestChange?.();
      },
      onError: (errors) => {
        setTeamJoinActionProcessing(false);
        // Handle validation errors or other errors
        const errorMessage = errors.message || Object.values(errors)[0] || 'An error occurred';
        toast.error(errorMessage);
      },
      onFinish: () => {
        setTeamJoinActionProcessing(false);
      }
    });
  };

  const handleCancelRequest = (team: object) => {
    if (!pendingRequestId) return;

    setTeamJoinActionProcessing(true);
    router.post(
      route('teams.cancel-user-join-request'),
      {
        request_id: pendingRequestId,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success(`Your request to join ${team.name} has been cancelled`);
          // Refresh the teams list and check pending requests
          fetchTeams(currentPage);
          checkPendingRequests();
          // Notify parent to refresh other components
          onRequestChange?.();
        },
        onError: (errors: any) => {
          toast.error(errors.error || 'Failed to cancel request');
        },
        onFinish: () => {
          setTeamJoinActionProcessing(false);
        },
      },
    );
  };

  // Skeleton component for individual user rows
  const UserRowSkeleton = () => (
    <div className="flex flex-wrap border-b p-4 text-sm lg:items-center">
      <div className="flex w-3/4 items-center gap-3 lg:w-1/4">
        <Skeleton className="h-10 w-10 rounded-full" />
        <div className="space-y-1">
          <Skeleton className="h-4 w-24" />
          <Skeleton className="h-3 w-32" />
        </div>
      </div>
      <div className="w-1/4 lg:w-1/4">
        <Skeleton className="h-4 w-12" />
      </div>
      <div className="w-1/4 lg:w-1/4">
        <Skeleton className="h-4 w-12" />
      </div>
      <div className="mt-2 w-full lg:mt-0 lg:w-1/4 lg:text-right">
        <Skeleton className="h-8 w-16" />
      </div>
    </div>
  );

  return (
    <Card>
      <CardHeader>
        <CardTitle>Find a Team To Join</CardTitle>
        <CardDescription>If you want to join an existing team, browse below and request to join.</CardDescription>
      </CardHeader>

      <CardContent className="space-y-6">
        {/* Search & Per Page Selector */}
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
            <Select value={perPage} onValueChange={setperPage}>
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

        {/* User Listing */}
        <div className="rounded-md border text-center">
          <div className="bg-muted text-muted-foreground grid grid-cols-4 border-b px-4 py-2 text-sm font-medium md:grid-cols-4">
            <div>Team</div>
            <div>Members</div>
            <div>Mileage</div>
            <div className="md:text-right">&nbsp;</div>
          </div>

          {loading ? (
            // Show skeleton rows while loading
            <>
              <UserRowSkeleton />
              <UserRowSkeleton />
              <UserRowSkeleton />
              <UserRowSkeleton />
              <UserRowSkeleton />
            </>
          ) : error ? (
            <div className="p-8 text-center text-red-500">{error}</div>
          ) : !teams || teams.data.length === 0 ? (
            <div className="text-muted-foreground p-8 text-center">No team found.</div>
          ) : (
            teams.data.map((member) => (
              <div key={member.id} className="flex flex-wrap border-b p-4 text-sm lg:items-center">
                <div className="flex w-3/4 items-center gap-3 lg:w-1/4">
                  <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 font-bold text-gray-500">
                    {member.name.charAt(0)}
                  </div>
                  <div>
                    <div className="font-medium">{member.name}</div>
                  </div>
                  <div>
                    {member.public_profile ? <Unlock className="size-4" /> : <Lock className="size-4" />}
                  </div>
                </div>
                <div className="w-1/4 lg:w-1/4">{member.members}</div>
                <div className="w-1/4 lg:w-1/4">{member.mileage}</div>
                <div className="mt-2 w-full lg:mt-0 lg:w-1/4 lg:text-right">
                  <Button
                    onClick={() => handleTeamJoinAction(member)}
                    disabled={teamJoinActionProcessing || (hasPendingRequest && pendingRequestTeamId !== member.id)}
                    onMouseEnter={() => setHoveredTeamId(member.id)}
                    onMouseLeave={() => setHoveredTeamId(null)}
                    variant={pendingRequestTeamId === member.id && hoveredTeamId === member.id ? 'destructive' : 'default'}
                  >
                    {pendingRequestTeamId === member.id
                      ? hoveredTeamId === member.id
                        ? 'Cancel Request'
                        : 'Requested Join'
                      : member.membership.text}
                  </Button>
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
            <Button variant="outline-primary" onClick={() => handlePagination((teams?.current_page || 1) - 1)} disabled={!teams?.prev_page_url}>
              Previous
            </Button>
            <Button variant="outline-primary" onClick={() => handlePagination((teams?.current_page || 1) + 1)} disabled={!teams?.next_page_url}>
              Next
            </Button>
          </div>
        )}
      </CardFooter>
    </Card>
  );
}
