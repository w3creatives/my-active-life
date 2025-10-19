import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Mail, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface TeamInvitation {
  id: number;
  team_id: number;
  team_name: string;
  inviter_name: string;
  event_id: number;
  event_name: string;
  created_at: string;
  status: string;
}

export default function TeamInvitations() {
  const { auth } = usePage<SharedData>().props;
  const [invitations, setInvitations] = useState<TeamInvitation[]>([]);
  const [loading, setLoading] = useState(true);
  const [processingInvitation, setProcessingInvitation] = useState<{ id: number; action: 'accept' | 'decline' } | null>(null);

  // Fetch user's pending team invitations
  const fetchInvitations = async () => {
    try {
      setLoading(true);
      const response = await fetch(route('user.team.invitations'));
      const data = await response.json();
      if (data.success) {
        setInvitations(data.data || []);
      }
    } catch (error) {
      console.error('Error fetching invitations:', error);
    } finally {
      setLoading(false);
    }
  };

  // Load invitations on component mount
  useEffect(() => {
    fetchInvitations();
  }, []);

  const handleAcceptInvitation = async (invitation: TeamInvitation) => {
    setProcessingInvitation({ id: invitation.id, action: 'accept' });

    router.post(
      route('user.team.invitation.accept'),
      {
        team_id: invitation.team_id,
        event_id: invitation.event_id,
      },
      {
        onSuccess: () => {
          toast.success(`You have successfully joined ${invitation.team_name}!`);
          // Remove the invitation from the list
          setInvitations((prev) => prev.filter((inv) => inv.id !== invitation.id));
          setProcessingInvitation(null);
          // Refresh the page after a brief delay to show the toast
          setTimeout(() => {
            router.visit(route('teams'), { preserveState: false });
          }, 500);
        },
        onError: (errors: any) => {
          toast.error(errors.error || 'Failed to accept invitation');
          setProcessingInvitation(null);
        },
      },
    );
  };

  const handleDeclineInvitation = async (invitation: TeamInvitation) => {
    setProcessingInvitation({ id: invitation.id, action: 'decline' });

    router.post(
      route('user.team.invitation.decline'),
      {
        team_id: invitation.team_id,
        event_id: invitation.event_id,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success(`You have declined the invitation to join ${invitation.team_name}`);
          // Remove the invitation from the list
          setInvitations((prev) => prev.filter((inv) => inv.id !== invitation.id));
        },
        onError: (errors: any) => {
          toast.error(errors.error || 'Failed to decline invitation');
        },
        onFinish: () => {
          setProcessingInvitation(null);
        },
      },
    );
  };

  // Don't render if no invitations
  if (loading) {
    return (
      <Card className="mb-6">
        <CardContent className="p-6">
          <div className="flex items-center justify-center">
            <div className="h-8 w-8 animate-spin rounded-full border-b-2 border-gray-900"></div>
            <span className="ml-2 text-sm text-gray-600">Loading invitations...</span>
          </div>
        </CardContent>
      </Card>
    );
  }

  if (invitations.length === 0) {
    return null;
  }

  return (
    <div className="mb-6 space-y-4">
      {invitations.map((invitation) => (
        <Card key={invitation.id} className="border-primary bg-primary/5">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 ">
              <Mail className="h-5 w-5" />
              Team Invitations
            </CardTitle>
            <CardDescription>
              What do you know? You were invited by another team to join! It's nice to be wanted! Click below to accept or decline.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="border-t border-primary pt-4">
              <p className="text-center font-medium ">
                {invitation.team_name} sent you an invitation to join them! You can accept the invitation or decline it below.
              </p>
            </div>

            <div className="flex justify-center gap-3">
              <Button
                onClick={() => handleAcceptInvitation(invitation)}
                disabled={processingInvitation?.id === invitation.id}
              >
                <Users className="h-4 w-4" />
                {processingInvitation?.id === invitation.id && processingInvitation.action === 'accept'
                  ? 'Accepting...'
                  : 'Accept Invitation'}
              </Button>

              <Button
                variant="outline-primary"
                onClick={() => handleDeclineInvitation(invitation)}
                disabled={processingInvitation?.id === invitation.id}
              >
                {processingInvitation?.id === invitation.id && processingInvitation.action === 'decline'
                  ? 'Declining...'
                  : 'Decline Invitation'}
              </Button>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
