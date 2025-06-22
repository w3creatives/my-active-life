import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Lock, LockOpen, Search } from 'lucide-react';
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
import { useEffect, useState } from 'react';
import axios from 'axios';

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

export default function FollowParticipant() {
  const [users, setUsers] = useState<Pagination<User> | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchUser, setSearchUser] = useState('');
  const [perPageUser, setPerPageUser] = useState('5');
  const [currentPage, setCurrentPage] = useState(1);

  const fetchUsers = async (page: number = currentPage) => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (searchUser) params.append('searchUser', searchUser);
      if (perPageUser) params.append('perPageUser', perPageUser);
      params.append('page', page.toString());

      const response = await axios.get(route('api.follow.available-users') + '?' + params.toString());
      setUsers(response.data.users);
      setCurrentPage(page);
    } catch (err) {
      setError('Failed to load available users');
      console.error('Error fetching available users:', err);
    } finally {
      setLoading(false);
    }
  };

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
  }, [searchUser, perPageUser]);

  const handlePagination = (page: number) => {
    fetchUsers(page);
  };

  // Skeleton component for individual user rows
  const UserRowSkeleton = () => (
    <div className="flex flex-wrap lg:items-center p-4 border-b text-sm">
      <div className="flex items-center gap-3 w-3/4 lg:w-1/5">
        <Skeleton className="w-10 h-10 rounded-full" />
        <div className="space-y-1">
          <Skeleton className="h-4 w-24" />
          <Skeleton className="h-3 w-32" />
        </div>
      </div>
      <div className='w-1/5 lg:w-1/5 flex justify-end lg:justify-start items-center'>
        <Skeleton className="w-5 h-5" />
      </div>
      <div className='w-1/4 lg:w-1/5'>
        <Skeleton className="h-4 w-16" />
      </div>
      <div className='w-1/4 lg:w-1/5'>
        <Skeleton className="h-4 w-12" />
      </div>
      <div className="w-full lg:w-1/5 lg:text-right mt-2 lg:mt-0">
        <Skeleton className="h-8 w-16" />
      </div>
    </div>
  );

  return (
    <Card>
      <CardHeader>
        <CardTitle>Choose People To Follow</CardTitle>
        <CardDescription>
          If you want to follow somebody, browse below and follow. If a person has a private profile, you must be approved to follow.
        </CardDescription>
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
            <Select
              value={perPageUser}
              onValueChange={setPerPageUser}
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

        {/* User Listing */}
        <div className="border rounded-md">
          <div className="grid grid-cols-1 md:grid-cols-5 px-4 py-2 border-b bg-muted text-muted-foreground text-sm font-medium">
            <div>User</div>
            <div>Privacy</div>
            <div>City</div>
            <div>State</div>
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
            <div className="p-8 text-center text-muted-foreground">No users found.</div>
          ) : (
            users.data.map((user) => (
            <div
              key={user.id}
              className="flex flex-wrap lg:items-center p-4 border-b text-sm"
            >
              <div className="flex items-center gap-3 w-3/4 lg:w-1/5">
                <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 font-bold">
                  {user.first_name.charAt(0)}
                  {user.last_name.charAt(0)}
                </div>
                <div>
                  <div className="font-medium">{user.display_name}</div>
                  <div className="text-muted-foreground">
                    {user.first_name} {user.last_name}
                  </div>
                </div>
              </div>
              <div className='w-1/5 lg:w-1/5 flex justify-end lg:justify-start items-center'>
                {user.public_profile ? (
                  <LockOpen className="text-gray-500 size-5" />
                ) : (
                  <Lock className="text-gray-500 size-5" />
                )}
              </div>
              <div className='w-1/4 lg:w-1/5'>{user.city}</div>
              <div className='w-1/4 lg:w-1/5'>{user.state}</div>
              <div className="w-full lg:w-1/5 lg:text-right mt-2 lg:mt-0">
                <Button variant="yellow" size="sm">
                  {user.following_status_text}
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
            <Button
              variant="outline"
              onClick={() => handlePagination((users?.current_page || 1) - 1)}
              disabled={!users?.prev_page_url}
            >
              Previous
            </Button>
            <Button
              variant="outline"
              onClick={() => handlePagination((users?.current_page || 1) + 1)}
              disabled={!users?.next_page_url}
            >
              Next
            </Button>
          </div>
        )}
      </CardFooter>
    </Card>
  );
}
