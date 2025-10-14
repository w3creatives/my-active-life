import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Link, router } from '@inertiajs/react';
import { ChevronLeft, Mail, CheckCircle, XCircle, Clock, AlertCircle } from 'lucide-react';
import { toast } from 'sonner';

interface Invitation {
  id: number;
  team_id: number;
  team_name: string;
  event_id: number;
  event_name: string;
  created_at: string;
  days_ago: number;
  status: string;
}

interface UserInvitationsProps {
  invitations: Invitation[];
  hasTeam: boolean;
}

export default function UserInvitations({ invitations, hasTeam }: UserInvitationsProps) {
    console.log(invitations);
  const handleAcceptInvitation = (invitation: Invitation) => {
    if (hasTeam) {
      toast.error('You are already a member of a team. Please leave your current team first.');
      return;
    }

    if (confirm(`Are you sure you want to accept the invitation to join ${invitation.team_name}?`)) {
      router.post(
        route('user.team.invitation.accept'),
        {
          team_id: invitation.team_id,
          event_id: invitation.event_id,
        },
        {
          preserveScroll: true,
          onSuccess: (response) => {
            const alert = response.props.alert as { type: string; message: string } | undefined;
            if (alert) {
              if (alert.type === 'success') {
                toast.success(alert.message);
              } else {
                toast.error(alert.message);
              }
            } else {
              toast.success('Invitation accepted successfully');
            }
          },
          onError: (errors) => {
            toast.error('Failed to accept invitation. Please try again.');
          },
        },
      );
    }
  };

  const handleDeclineInvitation = (invitation: Invitation) => {
    if (confirm(`Are you sure you want to decline the invitation to join ${invitation.team_name}?`)) {
      router.post(
        route('user.team.invitation.decline'),
        {
          team_id: invitation.team_id,
          event_id: invitation.event_id,
        },
        {
          preserveScroll: true,
          onSuccess: (response) => {
            const alert = response.props.alert as { type: string; message: string } | undefined;
            if (alert) {
              if (alert.type === 'success') {
                toast.success(alert.message);
              } else {
                toast.error(alert.message);
              }
            } else {
              toast.success('Invitation declined successfully');
            }
          },
          onError: (errors) => {
            toast.error('Failed to decline invitation. Please try again.');
          },
        },
      );
    }
  };

  const getStatusBadge = (daysAgo: number) => {
    if (daysAgo >= 30) {
      return <Badge variant="destructive">Expired</Badge>;
    } else if (daysAgo >= 7) {
      return <Badge variant="secondary">Old</Badge>;
    } else {
      return <Badge variant="default">Recent</Badge>;
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-top justify-between">
        <Heading title="Team Invitations" description="Manage team invitations you have received" />
        <div className="actions">
          <Link href={route('teams')}>
            <Button className="flex items-center gap-2">
              <ChevronLeft />
              Back to Teams
            </Button>
          </Link>
        </div>
      </div>

      {hasTeam && (
        <Card className="border-yellow-500 bg-yellow-50">
          <CardContent className="flex items-center gap-3 py-4">
            <AlertCircle className="h-5 w-5 text-yellow-600" />
            <p className="text-sm text-yellow-800">
              You are already a member of a team. You must leave your current team before accepting new invitations.
            </p>
          </CardContent>
        </Card>
      )}

      {invitations.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <Mail className="mb-4 h-12 w-12 text-gray-400" />
            <h3 className="mb-2 text-lg font-medium text-gray-900">No Team Invitations</h3>
            <p className="text-center text-gray-600">You have no pending team invitations at this time.</p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4">
          {invitations.map((invitation) => (
            <Card key={invitation.id}>
              <CardContent className="p-6">
                <div className="flex items-center justify-between">
                  <div className="flex-1">
                    <div className="flex items-center gap-2">
                      <h3 className="text-lg font-medium text-gray-900">{invitation.team_name}</h3>
                      {getStatusBadge(invitation.days_ago)}
                    </div>
                    <p className="mt-1 text-sm text-gray-600">{invitation.event_name}</p>
                    <div className="mt-2 flex items-center gap-2">
                      <Clock className="h-3 w-3 text-gray-400" />
                      <span className="text-xs text-gray-500">Invited {invitation.created_at}</span>
                    </div>
                  </div>
                  <div className="flex gap-2">
                    <Button
                      size="sm"
                      variant="default"
                      onClick={() => handleAcceptInvitation(invitation)}
                      disabled={hasTeam}
                      className="flex items-center gap-1"
                    >
                      <CheckCircle className="h-4 w-4" />
                      Accept
                    </Button>
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => handleDeclineInvitation(invitation)}
                      className="flex items-center gap-1 text-red-600 hover:text-red-700"
                    >
                      <XCircle className="h-4 w-4" />
                      Decline
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      <div className="rounded-md bg-gray-50 p-4 text-sm text-gray-500">
        <p>
          <strong>Note:</strong> Team invitations expire after 30 days. You can only accept invitations if you are not currently a member
          of any team. If you want to join a different team, you must first leave your current team.
        </p>
      </div>
    </div>
  );
}
