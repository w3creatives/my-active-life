import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { UserPlus, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface MembershipRequest {
  id: number;
  user_id: number;
  user_name: string;
  user_email: string;
  status: string;
  created_at: string;
  days_ago: number;
}

export default function TeamMembershipRequests() {
  const { auth } = usePage<SharedData>().props;
  const [requests, setRequests] = useState<MembershipRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [processingRequest, setProcessingRequest] = useState<{ id: number; action: 'accept' | 'decline' } | null>(null);

  // Fetch team membership requests
  const fetchRequests = async () => {
    try {
      setLoading(true);
      const response = await fetch(route('teams.membership-requests'));
      const data = await response.json();
      if (data.success) {
        setRequests(data.data || []);
      }
    } catch (error) {
      console.error('Error fetching membership requests:', error);
    } finally {
      setLoading(false);
    }
  };

  // Load requests on component mount
  useEffect(() => {
    fetchRequests();
  }, []);

  const handleAcceptRequest = async (request: MembershipRequest) => {
    setProcessingRequest({ id: request.id, action: 'accept' });

    router.post(
      route('teams.handle-membership-request'),
      {
        request_id: request.id,
        action: 'accept',
      },
      {
        onSuccess: () => {
          toast.success(`${request.user_name} has been added to the team!`);
          // Remove the request from local state and refetch to update the list
          setRequests((prev) => prev.filter((req) => req.id !== request.id));
          setProcessingRequest(null);
          // Refresh the page after a brief delay to show the toast
          setTimeout(() => {
            router.visit(route('teams'), { preserveState: false });
          }, 500);
        },
        onError: (errors: any) => {
          toast.error(errors.error || 'Failed to accept request');
          setProcessingRequest(null);
        },
      },
    );
  };

  const handleDeclineRequest = async (request: MembershipRequest) => {
    setProcessingRequest({ id: request.id, action: 'decline' });

    router.post(
      route('teams.handle-membership-request'),
      {
        request_id: request.id,
        action: 'decline',
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success(`Request from ${request.user_name} has been declined`);
          // Remove the request from the list
          setRequests((prev) => prev.filter((req) => req.id !== request.id));
        },
        onError: (errors: any) => {
          toast.error(errors.error || 'Failed to decline request');
        },
        onFinish: () => {
          setProcessingRequest(null);
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
            <span className="ml-2 text-sm text-gray-600">Loading membership requests...</span>
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
    <Card className="mb-6">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <UserPlus className="h-5 w-5" />
          These awesome people would like to join your team!
        </CardTitle>
        <CardDescription>
          Here are the users who expressed interest in joining your team:
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        {requests.map((request) => (
          <div
            key={request.id}
            className="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
          >
            <div className="flex-1">
              <div className="flex items-center gap-2">
                <Users className="h-5 w-5 text-gray-500" />
                <div>
                  <p className="font-medium text-gray-900">{request.user_name}</p>
                  <p className="text-sm text-gray-600">{request.user_email}</p>
                </div>
              </div>
            </div>

            <div className="flex gap-2">
              <Button
                onClick={() => handleAcceptRequest(request)}
                disabled={processingRequest?.id === request.id}
                size="sm"
                className="flex items-center gap-1"
              >
                {processingRequest?.id === request.id && processingRequest.action === 'accept'
                  ? 'Accepting...'
                  : 'Accept'}
              </Button>

              <Button
                variant="outline"
                onClick={() => handleDeclineRequest(request)}
                disabled={processingRequest?.id === request.id}
                size="sm"
                className="flex items-center gap-1"
              >
                {processingRequest?.id === request.id && processingRequest.action === 'decline'
                  ? 'Declining...'
                  : 'Decline'}
              </Button>
            </div>
          </div>
        ))}
      </CardContent>
    </Card>
  );
}
