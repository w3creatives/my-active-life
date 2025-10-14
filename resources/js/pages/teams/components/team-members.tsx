import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import type { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { Crown, Search } from 'lucide-react';
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

export default function TeamMembers() {
  const { team, auth } = usePage<SharedData>().props;
  const teamData = team as any; // Type assertion to avoid TypeScript errors

  const [users, setUsers] = useState<Pagination<User> | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchUser, setSearchUser] = useState('');
  const [perPage, setperPage] = useState('5');
  const [currentPage, setCurrentPage] = useState(1);
  const [followingId, setFollowingId] = useState<number | null>(null);
  const [leavingTeam, setLeavingTeam] = useState(false);
  const [removingMember, setRemovingMember] = useState<number | null>(null);

  const fetchUsers = async (page: number = currentPage) => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (searchUser) params.append('searchUser', searchUser);
      if (perPage) params.append('perPage', perPage);
      params.append('teamId', teamData?.id);
      params.append('page', page.toString());

      const response = await axios.get(route('teams.members') + '?' + params.toString());
      setUsers(response.data.members);
      setCurrentPage(page);
    } catch (err) {
      setError('Failed to load available team members');
      console.error('Error fetching available team members:', err);
    } finally {
      setLoading(false);
    }
  };

  function handleLeaveTeam(member: any) {
    // Prevent multiple clicks
    if (auth.user.id !== member.id || leavingTeam) {
      return;
    }

    // Show confirmation dialog
    if (!confirm('Are you sure you want to leave this team? This action cannot be undone.')) {
      return;
    }

    setLeavingTeam(true);

    router.post(
      route('teams.leave-team'),
      {
        team_id: teamData?.id,
        user_id: member.id,
        event_id: teamData?.event_id,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success('You have successfully left the team.');
          // Redirect to teams page since user is no longer a member
          router.visit(route('teams'));
        },
        onError: (errors) => {
          const errorMessage = errors.error || 'Failed to leave team. Please try again.';
          toast.error(errorMessage);
          setLeavingTeam(false);
        },
      },
    );
  }

  function handleRemoveMember(member: any) {
    // Prevent multiple clicks
    if (removingMember === member.id) {
      return;
    }

    // Show confirmation dialog
    if (!confirm(`Are you sure you want to remove ${member.name} from the team? This action cannot be undone.`)) {
      return;
    }

    setRemovingMember(member.id);

    router
      .post(
        route('teams.remove-member'),
        {
          team_id: teamData?.id,
          member_id: member.id,
          event_id: teamData?.event_id,
        },
        {
          preserveScroll: true,
          onSuccess: () => {
            toast.success(`${member.name} has been removed from the team.`);
            // Refresh the team members list
            fetchUsers(currentPage);
          },
          onError: (errors) => {
            const errorMessage = errors.error || 'Failed to remove team member. Please try again.';
            toast.error(errorMessage);
          },
        },
      )
      .finally(() => {
        setRemovingMember(null);
      });
  }

  useEffect(() => {
    fetchUsers(1); // Reset to page 1 on initial load
  }, []);

  // Debounced search trigger - reset to page 1 when search changes
  useEffect(() => {
    const timeout = setTimeout(() => {
      setCurrentPage(1);
      fetchUsers(1);
    }, 500);
    return () => clearTimeout(timeout);
  }, [searchUser, perPage]);

  const handlePagination = (page: number) => {
    fetchUsers(page);
  };

  // Skeleton component for individual user rows
  const UserRowSkeleton = () => (
    <div className="flex flex-wrap border-b p-4 text-sm lg:items-center">
      <div className="flex w-3/4 items-center gap-3 lg:w-1/3">
        <Skeleton className="h-10 w-10 rounded-full" />
        <div className="space-y-1">
          <Skeleton className="h-4 w-24" />
          <Skeleton className="h-3 w-32" />
        </div>
      </div>

      <div className="w-1/4 lg:w-1/3">
        <Skeleton className="h-4 w-12" />
      </div>
      <div className="mt-2 w-full lg:mt-0 lg:w-1/3 lg:text-right">
        <Skeleton className="h-8 w-16" />
      </div>
    </div>
  );

  return (
    <Card>
      <CardHeader>
        <CardTitle>Team Members</CardTitle>
        <CardDescription>Below is a list of your current teammates.</CardDescription>
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
              value={searchUser}
              onChange={(e) => setSearchUser(e.target.value)}
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
        <div className="rounded-md border">
          <div className="bg-muted text-muted-foreground grid grid-cols-3 border-b px-4 py-2 text-sm font-medium md:grid-cols-3">
            <div>Member Name</div>
            <div>Miles</div>
            <div className="md:text-right">Action</div>
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
          ) : !users || users.data.length === 0 ? (
            <div className="text-muted-foreground p-8 text-center">No team members found.</div>
          ) : (
            users.data.map((member) => (
              <div key={member.id} className="flex flex-wrap border-b p-4 text-sm lg:items-center">
                <div className="flex w-3/4 items-center gap-3 lg:w-1/3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 font-bold text-gray-500">
                    {member.name.charAt(0)}
                  </div>
                  <div>
                    <div className="flex items-center gap-2 font-medium">
                      {member.name}
                      {member.id === teamData?.owner_id && <Crown className="h-4 w-4 text-yellow-600" title="Team Admin" />}
                    </div>
                  </div>
                </div>

                <div className="w-1/4 lg:w-1/3">{ member.miles }</div>
                <div className="mt-2 w-full lg:mt-0 lg:w-1/3 lg:text-right">
                  <div className="flex justify-end gap-2">
                    {member.id === auth.user.id ? (
                      <Button variant="destructive" size="sm" onClick={() => handleLeaveTeam(member)} disabled={leavingTeam}>
                        {leavingTeam ? 'Leaving...' : 'Leave Team'}
                      </Button>
                    ) : teamData?.owner_id === auth.user.id ? (
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleRemoveMember(member)}
                        disabled={removingMember === member.id}
                        className="border-red-300 text-red-700 hover:bg-red-50"
                      >
                        {removingMember === member.id ? 'Removing...' : 'Remove Member'}
                      </Button>
                    ) : null}
                  </div>
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
            <Button variant="outline" onClick={() => handlePagination((users?.current_page || 1) - 1)} disabled={!users?.prev_page_url}>
              Previous
            </Button>
            <Button variant="outline" onClick={() => handlePagination((users?.current_page || 1) + 1)} disabled={!users?.next_page_url}>
              Next
            </Button>
          </div>
        )}
      </CardFooter>
    </Card>
  );
}
