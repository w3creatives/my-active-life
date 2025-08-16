import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface Participant {
  id: number;
  display_name: string;
  total_miles: number;
}

interface ParticipantsData {
  data: Participant[];
}

export default function FollowingParticipants() {
  const [participants, setParticipants] = useState<ParticipantsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [unfollowingId, setUnfollowingId] = useState<number | null>(null);

  const fetchUserFollowings = async () => {
    try {
      setLoading(true);
      const response = await axios.get(route('api.follow.user-followings'));
      setParticipants(response.data.userFollowings);
    } catch (err) {
      setError('Failed to load following participants');
      console.error('Error fetching user followings:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchUserFollowings();
  }, []);

  function handleUnfollow(userId: number, displayName: string) {
    setUnfollowingId(userId);
    router.post(
      '/unfollow/user',
      { user_id: userId, event_id: 64 },
      {
        preserveScroll: true,
        onSuccess: () => {
          // Always show success message and refresh data on successful response
          toast.success(`You have successfully unfollowed ${displayName}.`);
          // Refresh the data after successful unfollow
          fetchUserFollowings();
        },
        onError: (errors) => {
          // Handle validation errors or other errors
          const errorMessage = errors.error || `Failed to unfollow ${displayName}. Please try again.`;
          toast.error(errorMessage);
        },
        onFinish: () => {
          setUnfollowingId(null);
        },
      },
    );
  }

  // Skeleton component for individual participant rows
  const ParticipantSkeleton = () => (
    <div className="flex items-center justify-between border-b pb-4 last:border-b-0">
      {/* Avatar + Display Name */}
      <div className="flex items-center gap-3">
        <Skeleton className="size-10 rounded-full" />
        <Skeleton className="h-4 w-32" />
      </div>

      {/* Progress Bar */}
      <div className="mx-6 flex-1">
        <Skeleton className="h-2 w-full rounded-full" />
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
        <CardTitle>People I Follow</CardTitle>
        <CardDescription>Here's a list of people you’re currently following.</CardDescription>
      </CardHeader>

      <CardContent className="space-y-4">
        {loading ? (
          // Show skeleton rows while loading
          <div className="space-y-4">
            <ParticipantSkeleton />
            <ParticipantSkeleton />
            <ParticipantSkeleton />
          </div>
        ) : error ? (
          <div className="text-center text-red-500">{error}</div>
        ) : !participants || participants.data.length === 0 ? (
          <div className="text-muted-foreground text-center">You are not following anyone. Boo-hoo.</div>
        ) : (
          <div className="space-y-4">
            {participants.data.map((person) => (
              <div key={person.id} className="flex items-center justify-between border-b pb-4 last:border-b-0">
                {/* Avatar + Display Name */}
                <div className="flex items-center gap-3">
                  <div
                    className="flex size-10 items-center justify-center rounded-full bg-gray-200 text-sm font-medium text-gray-500 uppercase"
                    aria-label={`Initial of ${person.display_name}`}
                  >
                    {person.display_name?.charAt(0) || 'U'}
                  </div>
                  <div className="font-medium">{person.display_name}</div>
                </div>

                {/* Progress Bar */}
                <div className="mx-6 flex-1">
                  <div className="h-2 w-full rounded-full bg-gray-200">
                    <div
                      className="bg-gray h-2 rounded-full"
                      style={{ width: `${Math.min(person.total_miles, 100)}%` }}
                      role="progressbar"
                      aria-valuenow={Math.min(person.total_miles, 100)}
                      aria-valuemin={0}
                      aria-valuemax={100}
                    ></div>
                  </div>
                </div>

                {/* Miles & Unfollow */}
                <div className="flex items-center gap-4">
                  <div className="text-muted-foreground text-sm font-medium whitespace-nowrap">{person.total_miles} mi</div>
                  <Button
                    variant="danger"
                    size="sm"
                    onClick={() => handleUnfollow(person.id, person.display_name)}
                    disabled={unfollowingId === person.id}
                  >
                    {unfollowingId === person.id ? 'Unfollowing' : 'Unfollow'}
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
