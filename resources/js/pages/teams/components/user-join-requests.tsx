import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Clock, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface JoinRequest {
  id: number;
  team_id: number;
  team_name: string;
  status: string;
  created_at: string;
  days_ago: number;
}

interface UserJoinRequestsProps {
  refreshTrigger?: number;
}

export default function UserJoinRequests({ refreshTrigger }: UserJoinRequestsProps) {
  const { auth } = usePage<SharedData>().props;
  const [requests, setRequests] = useState<JoinRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [cancellingRequest, setCancellingRequest] = useState<number | null>(null);

  // Fetch user's outgoing join requests
  const fetchRequests = async () => {
    try {
      setLoading(true);
      const response = await fetch(route('teams.user-join-requests'));
      const data = await response.json();
      if (data.success) {
        setRequests(data.data || []);
      }
    } catch (error) {
      console.error('Error fetching join requests:', error);
    } finally {
      setLoading(false);
    }
  };

  // Load requests on component mount and when refreshTrigger changes
  useEffect(() => {
    fetchRequests();
  }, [refreshTrigger]);

  const handleCancelRequest = async (request: JoinRequest) => {
    setCancellingRequest(request.id);

    router.post(
      route('teams.cancel-user-join-request'),
      {
        request_id: request.id,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success(`Your request to join ${request.team_name} has been cancelled`);
          // Remove the request from the list
          setRequests((prev) => prev.filter((req) => req.id !== request.id));
        },
        onError: (errors: any) => {
          toast.error(errors.error || 'Failed to cancel request');
        },
        onFinish: () => {
          setCancellingRequest(null);
        },
      },
    );
  };

  // Don't render if loading
  if (loading) {
    return (
      <Card className="mb-6">
        <CardContent className="p-6">
          <div className="flex items-center justify-center">
            <div className="h-8 w-8 animate-spin rounded-full border-b-2 border-gray-900"></div>
            <span className="ml-2 text-sm text-gray-600">Loading your join requests...</span>
          </div>
        </CardContent>
      </Card>
    );
  }

  // Don't render if no requests
  if (requests.length === 0) {
    return null;
  }

  return (
    <Card className="mb-6 border-orange-200 bg-orange-50">
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-orange-800">
          <Clock className="size-5" />
          Pending Join Requests
        </CardTitle>
        <CardDescription className="text-orange-700">
          You have requested to join team
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-3">
        {requests.map((request) => (
          <div
            key={request.id}
            className="flex items-center justify-between rounded-lg border border-orange-200 bg-white p-4 shadow-sm"
          >
            <div className="flex-1">
              <div className="flex items-center gap-2">
                <Clock className="size-5 text-orange-500" />
                <div>
                  <p className="font-medium text-gray-900">{request.team_name}</p>
                  <p className="text-xs text-gray-500">Waiting for team approval</p>
                </div>
              </div>
            </div>

            <div>
              <Button
                variant="outline"
                size="sm"
                onClick={() => handleCancelRequest(request)}
                disabled={cancellingRequest === request.id}
                className="flex items-center gap-1 border-orange-300 text-orange-700 hover:bg-orange-100"
              >
                <X className="h-4 w-4" />
                {cancellingRequest === request.id ? 'Cancelling...' : 'Cancel Request'}
              </Button>
            </div>
          </div>
        ))}
      </CardContent>
    </Card>
  );
}
