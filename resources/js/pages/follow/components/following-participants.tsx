import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import React, { useState } from 'react';

interface Participant {
  id: number;
  display_name: string;
  total_miles: number;
}

interface Props {
  participants: {
    data: Participant[];
  };
}

export default function FollowingParticipants({ participants }: Props) {
  const [unfollowingId, setUnfollowingId] = useState<number | null>(null);

  function handleUnfollow(userId: number) {
    setUnfollowingId(userId);
    router.post(
      '/unfollow/user',
      { user_id: userId, event_id: 64 },
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success('You have successfully unfollowed the user.');
        },
        onFinish: () => {
          setUnfollowingId(null);
        },
      }
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>People I Follow</CardTitle>
        <CardDescription>
          Here's a list of people you’re currently following.
        </CardDescription>
      </CardHeader>

      <CardContent className="space-y-4">
        {participants.data.length === 0 ? (
          <div className="text-muted-foreground text-center">
            You are not following anyone. Boo-hoo.
          </div>
        ) : (
          <div className="space-y-4">
            {participants.data.map((person) => (
              <div
                key={person.id}
                className="flex items-center justify-between border-b pb-4 last:border-b-0"
              >
                {/* Avatar + Display Name */}
                <div className="flex items-center gap-3">
                  <div
                    className="size-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 uppercase text-sm font-medium"
                    aria-label={`Initial of ${person.display_name}`}
                  >
                    {person.display_name?.charAt(0) || 'U'}
                  </div>
                  <div className="font-medium">{person.display_name}</div>
                </div>

                {/* Progress Bar */}
                <div className="flex-1 mx-6">
                  <div className="w-full bg-gray-200 rounded-full h-2">
                    <div
                      className="bg-primary h-2 rounded-full"
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
                  <div className="text-sm font-medium text-muted-foreground whitespace-nowrap">
                    {person.total_miles.toFixed(1)} mi
                  </div>
                  <Button
                    variant="danger"
                    size="sm"
                    onClick={() => handleUnfollow(person.id)}
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
