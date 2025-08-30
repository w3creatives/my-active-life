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
  const [processingInvitation, setProcessingInvitation] = useState<number | null>(null);



  // Fetch user's pending team invitations
  const fetchInvitations = async () => {
    try {
      setLoading(true);
      const response = await fetch(route('api.user.team.invitations'));
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
    setProcessingInvitation(invitation.id);

    router.post(
      route('api.user.team.invitation.accept'),
      {
        team_id: invitation.team_id,
        event_id: invitation.event_id,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success(`You have successfully joined ${invitation.team_name}!`);
          // Remove the invitation from the list
          setInvitations(prev => prev.filter(inv => inv.id !== invitation.id));
          // Refresh the page to show the user is now part of a team
          router.reload();
        },
        onError: (errors: any) => {
          toast.error(errors.error || 'Failed to accept invitation');
        },
      },
    ).finally(() => {
      setProcessingInvitation(null);
    });
  };

  const handleDeclineInvitation = async (invitation: TeamInvitation) => {
    setProcessingInvitation(invitation.id);

    router.post(
      route('api.user.team.invitation.decline'),
      {
        team_id: invitation.team_id,
        event_id: invitation.event_id,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success(`You have declined the invitation to join ${invitation.team_name}`);
          // Remove the invitation from the list
          setInvitations(prev => prev.filter(inv => inv.id !== invitation.id));
        },
        onError: (errors: any) => {
          toast.error(errors.error || 'Failed to decline invitation');
        },
      },
    ).finally(() => {
      setProcessingInvitation(null);
    });
  };

  // Don't render if no invitations
  if (loading) {
    return (
      <Card className="mb-6">
        <CardContent className="p-6">
          <div className="flex items-center justify-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
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
    <div className="space-y-4 mb-6">
      {invitations.map((invitation) => (
        <Card key={invitation.id} className="border-blue-200 bg-blue-50">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-blue-800">
              <Mail className="h-5 w-5" />
              Team Invitations
            </CardTitle>
            <CardDescription className="text-blue-700">
              What do you know? You were invited by another team to join! It's nice to be wanted! Click below to accept or decline.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="border-t border-blue-200 pt-4">
              <p className="text-center text-blue-800 font-medium">
                {invitation.team_name} sent you an invitation to join them! You can accept the invitation or decline it below.
              </p>
            </div>
            
            <div className="flex gap-3 justify-center">
              <Button
                onClick={() => handleAcceptInvitation(invitation)}
                disabled={processingInvitation === invitation.id}
                className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700"
              >
                <Users className="h-4 w-4" />
                {processingInvitation === invitation.id ? 'Accepting...' : 'Accept Invitation'}
              </Button>
              
              <Button
                variant="outline"
                onClick={() => handleDeclineInvitation(invitation)}
                disabled={processingInvitation === invitation.id}
                className="flex items-center gap-2 border-blue-300 text-blue-700 hover:bg-blue-100"
              >
                {processingInvitation === invitation.id ? 'Declining...' : 'Decline Invitation'}
              </Button>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
