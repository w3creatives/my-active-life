import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import type { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { Search } from 'lucide-react';
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
        event_id: teamData?.event_id 
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
    <div className="flex flex-wrap lg:items-center p-4 border-b text-sm">
      <div className="flex items-center gap-3 w-3/4 lg:w-1/3">
        <Skeleton className="rounded-full w-10 h-10" />
        <div className="space-y-1">
          <Skeleton className="w-24 h-4" />
          <Skeleton className="w-32 h-3" />
        </div>
      </div>

      <div className="w-1/4 lg:w-1/3">
        <Skeleton className="w-12 h-4" />
      </div>
      <div className="mt-2 lg:mt-0 w-full lg:w-1/3 lg:text-right">
        <Skeleton className="w-16 h-8" />
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
        <div className="flex gap-5 min-h-12">
          <div className="relative w-full">
            <Search className="top-1/2 left-3 absolute w-4 h-4 text-muted-foreground -translate-y-1/2" />
            <Input
              type="search"
              placeholder="Search..."
              className="pl-10 h-full"
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
        <div className="border rounded-md">
          <div className="grid grid-cols-3 md:grid-cols-3 bg-muted px-4 py-2 border-b font-medium text-muted-foreground text-sm">
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
            </>
          ) : error ? (
            <div className="p-8 text-red-500 text-center">{error}</div>
          ) : !users || users.data.length === 0 ? (
            <div className="p-8 text-muted-foreground text-center">No team members found.</div>
          ) : (
            users.data.map((member) => (
              <div key={member.id} className="flex flex-wrap lg:items-center p-4 border-b text-sm">
                <div className="flex items-center gap-3 w-3/4 lg:w-1/3">
                  <div className="flex justify-center items-center bg-gray-200 rounded-full w-10 h-10 font-bold text-gray-500">
                    {member.name.charAt(0)}
                  </div>
                  <div>
                    <div className="font-medium">{member.name}</div>
                  </div>
                </div>

                <div className="w-1/4 lg:w-1/3">{member.miles}</div>
                <div className="mt-2 lg:mt-0 w-full lg:w-1/3 lg:text-right">
                  {member.id === auth.user.id && (
                    <Button 
                      variant="destructive" 
                      size="sm" 
                      onClick={() => handleLeaveTeam(member)}
                      disabled={leavingTeam}
                    >
                      {leavingTeam ? 'Leaving...' : 'Leave Team'}
                    </Button>
                  )}
                </div>
              </div>
            ))
          )}
        </div>
      </CardContent>

      <CardFooter className="justify-end">
        {loading ? (
          <div className="flex gap-2">
            <Skeleton className="w-20 h-10" />
            <Skeleton className="w-16 h-10" />
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
