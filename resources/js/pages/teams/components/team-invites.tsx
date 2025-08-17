import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Mail, Clock, UserCheck, UserX, RefreshCw, Trash2 } from 'lucide-react';
import { router, usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { type SharedData } from '@/types';

interface Invite {
  id: number;
  user_id: number;
  user_name: string;
  user_email: string;
  status: string;
  created_at: string;
  days_ago: number;
}

interface TeamInvitesProps {
  pendingInvites: Invite[];
  team: any;
}

export default function TeamInvites({ pendingInvites, team }: TeamInvitesProps) {
  const handleCancelInvite = (inviteId: number) => {
    if (confirm('Are you sure you want to cancel this invite?')) {
      router.post(route('teams.cancel-invite'), {
        invite_id: inviteId
      }, {
        preserveScroll: true,
        onSuccess: (response) => {
          let alert = response.props.alert as { type: string; message: string } | undefined;
          if (alert) {
            if (alert.type === 'success') {
              toast.success(alert.message);
            } else {
              toast.error(alert.message);
            }
          }
        },
        onError: (errors) => {
          toast.error('Failed to cancel invite. Please try again.');
        },
      });
    }
  };

  const handleResendInvite = (inviteId: number) => {
    router.post(route('teams.resend-invite'), {
      invite_id: inviteId
    }, {
      preserveScroll: true,
      onSuccess: (response) => {
        let alert = response.props.alert as { type: string; message: string } | undefined;
        if (alert) {
          if (alert.type === 'success') {
            toast.success(alert.message);
          } else {
            toast.error(alert.message);
          }
        }
      },
      onError: (errors) => {
        toast.error('Failed to resend invite. Please try again.');
      },
    });
  };

  const handleCancelExpiredInvites = () => {
    if (confirm('Are you sure you want to cancel all expired invites (older than 30 days)?')) {
      router.post(route('teams.cancel-expired-invites'), {}, {
        preserveScroll: true,
        onSuccess: (response) => {
          let alert = response.props.alert as { type: string; message: string } | undefined;
          if (alert) {
            if (alert.type === 'success') {
              toast.success(alert.message);
            } else {
              toast.error(alert.message);
            }
          }
        },
        onError: (errors) => {
          toast.error('Failed to cancel expired invites. Please try again.');
        },
      });
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

  const getExpiredInvitesCount = () => {
    return pendingInvites.filter(invite => invite.days_ago >= 30).length;
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Team Invites</h2>
          <p className="text-gray-600">
            Manage pending invitations for {team?.name}
          </p>
        </div>
        <div className="flex gap-2">
          {getExpiredInvitesCount() > 0 && (
            <Button
              variant="outline"
              onClick={handleCancelExpiredInvites}
              className="flex items-center gap-2"
            >
              <Trash2 className="h-4 w-4" />
              Cancel Expired ({getExpiredInvitesCount()})
            </Button>
          )}
        </div>
      </div>

      {pendingInvites.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <Mail className="h-12 w-12 text-gray-400 mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">No Pending Invites</h3>
            <p className="text-gray-600 text-center">
              There are no pending invitations for this team.
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4">
          {pendingInvites.map((invite) => (
            <Card key={invite.id}>
              <CardContent className="p-6">
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-4">
                    <div className="flex-shrink-0">
                      <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                        <UserCheck className="h-5 w-5 text-gray-600" />
                      </div>
                    </div>
                    <div>
                      <h3 className="text-lg font-medium text-gray-900">
                        {invite.user_name}
                      </h3>
                      <p className="text-sm text-gray-600 flex items-center gap-1">
                        <Mail className="h-3 w-3" />
                        {invite.user_email}
                      </p>
                      <div className="flex items-center gap-2 mt-1">
                        <Clock className="h-3 w-3 text-gray-400" />
                        <span className="text-xs text-gray-500">
                          Invited {invite.created_at} ({invite.days_ago} days ago)
                        </span>
                      </div>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    {getStatusBadge(invite.days_ago)}
                    <div className="flex gap-1">
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => handleResendInvite(invite.id)}
                        className="flex items-center gap-1"
                      >
                        <RefreshCw className="h-3 w-3" />
                        Resend
                      </Button>
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => handleCancelInvite(invite.id)}
                        className="flex items-center gap-1 text-red-600 hover:text-red-700"
                      >
                        <UserX className="h-3 w-3" />
                        Cancel
                      </Button>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      <div className="text-sm text-gray-500 bg-gray-50 p-4 rounded-md">
        <p>
          <strong>Note:</strong> Invites expire after 30 days. You can resend invites to remind users or cancel them if needed. 
          Only team members can view and manage invites.
        </p>
      </div>
    </div>
  );
}
