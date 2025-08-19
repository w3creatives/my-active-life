import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type SharedData } from '@/types';
import { AlertTriangle, Users, Crown } from 'lucide-react';
import { useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState, useEffect } from 'react';
import { toast } from 'sonner';
import axios from 'axios';
import { router } from '@inertiajs/react';

interface TeamMember {
  id: number;
  name: string;
  miles: number;
  team_id: number;
  event_id: number;
}

interface Pagination<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  next_page_url: string | null;
  prev_page_url: string | null;
}

export default function AdminControls() {
  // @ts-ignore
  const { team, auth } = usePage<SharedData>().props;
  const teamData = team as any; // Type assertion to avoid TypeScript errors
  const [isTransferDialogOpen, setIsTransferDialogOpen] = useState(false);
  const [teamMembers, setTeamMembers] = useState<Pagination<TeamMember> | null>(null);
  const [loadingMembers, setLoadingMembers] = useState(false);
  const [selectedMemberId, setSelectedMemberId] = useState<number | null>(null);

  const { post, processing } = useForm();

  // Only show admin controls if user is the team owner
  if (!teamData || teamData.owner_id !== auth.user.id) {
    return null;
  }

  const fetchTeamMembers = async () => {
    try {
      setLoadingMembers(true);
      const params = new URLSearchParams();
      params.append('teamId', teamData.id);
      params.append('perPage', '100'); // Get all members for transfer

      const response = await axios.get(route('teams.members') + '?' + params.toString());
      setTeamMembers(response.data.members);
    } catch (err) {
      toast.error('Failed to load team members');
      console.error('Error fetching team members:', err);
    } finally {
      setLoadingMembers(false);
    }
  };

  const handleDissolveTeam: FormEventHandler = (e) => {
    e.preventDefault();

    if (!confirm('Are you sure you want to dissolve this team? This action cannot be undone and will remove all team members.')) {
      return;
    }

    post(route('teams.dissolve'), {
      data: { team_id: teamData.id },
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Team has been dissolved successfully');
        // Redirect to teams page
        router.visit(route('teams'));
      },
      onError: (errors) => {
        toast.error(errors.error || 'Failed to dissolve team');
      },
    });
  };

  const handleTransferAdminRole = () => {
    if (!selectedMemberId) {
      toast.error('Please select a team member to transfer admin role to');
      return;
    }

    post(route('teams.transfer-admin-role'), {
      data: { 
        team_id: teamData.id,
        member_id: selectedMemberId 
      },
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Team admin role has been transferred successfully');
        setIsTransferDialogOpen(false);
        setSelectedMemberId(null);
        // Refresh the page to update the UI
        router.reload();
      },
      onError: (errors) => {
        toast.error(errors.error || 'Failed to transfer admin role');
      },
    });
  };

  const openTransferDialog = () => {
    setIsTransferDialogOpen(true);
    fetchTeamMembers();
  };

  return (
    <Card className="bg-red-50 border-red-200">
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-red-800">
          <AlertTriangle className="w-5 h-5" />
          Other Admin Controls
        </CardTitle>
        <CardDescription className="text-red-700">
          These actions are irreversible and will affect all team members.
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex sm:flex-row flex-col gap-3">
          <Button
            variant="danger"
            onClick={handleDissolveTeam}
            disabled={processing}
            className="flex items-center gap-2"
          >
            <AlertTriangle className="w-4 h-4" />
            Dissolve Team
          </Button>

          <Dialog open={isTransferDialogOpen} onOpenChange={setIsTransferDialogOpen}>
            <DialogTrigger asChild>
              <Button
                variant="outline"
                onClick={openTransferDialog}
                className="flex items-center gap-2 hover:bg-orange-50 border-orange-300 text-orange-700"
              >
                <Crown className="w-4 h-4" />
                Transfer Admin Role
              </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
              <DialogHeader>
                <DialogTitle className="flex items-center gap-2">
                  <Crown className="w-5 h-5" />
                  Transfer Admin Role
                </DialogTitle>
                <DialogDescription>
                  Select a team member to transfer the admin role to. You will no longer be the team admin after this action.
                </DialogDescription>
              </DialogHeader>
              
              <div className="space-y-4">
                {loadingMembers ? (
                  <div className="py-4 text-center">
                    <div className="mx-auto border-gray-900 border-b-2 rounded-full w-8 h-8 animate-spin"></div>
                    <p className="mt-2 text-gray-600 text-sm">Loading team members...</p>
                  </div>
                ) : teamMembers && teamMembers.data.length > 0 ? (
                  <div className="space-y-2 max-h-60 overflow-y-auto">
                    {teamMembers.data.map((member) => (
                      <div
                        key={member.id}
                        className={`flex items-center justify-between p-3 rounded-lg border cursor-pointer transition-colors ${
                          selectedMemberId === member.id
                            ? 'border-blue-500 bg-blue-50'
                            : 'border-gray-200 hover:border-gray-300'
                        }`}
                        onClick={() => setSelectedMemberId(member.id)}
                      >
                        <div className="flex items-center gap-3">
                          <div className="flex justify-center items-center bg-gray-100 rounded-full w-8 h-8">
                            <Users className="w-4 h-4 text-gray-600" />
                          </div>
                          <div>
                            <p className="font-medium text-sm">{member.name}</p>
                            <p className="text-gray-500 text-xs">{member.miles} miles</p>
                          </div>
                        </div>
                        {selectedMemberId === member.id && (
                          <Crown className="w-4 h-4 text-blue-600" />
                        )}
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="py-4 text-center">
                    <p className="text-gray-600 text-sm">No team members found</p>
                  </div>
                )}

                {selectedMemberId && (
                  <div className="flex gap-2 pt-4">
                    <Button
                      onClick={handleTransferAdminRole}
                      disabled={processing}
                      className="flex-1"
                    >
                      {processing ? 'Transferring...' : 'Transfer Admin Role'}
                    </Button>
                    <Button
                      variant="outline"
                      onClick={() => {
                        setIsTransferDialogOpen(false);
                        setSelectedMemberId(null);
                      }}
                      className="flex-1"
                    >
                      Cancel
                    </Button>
                  </div>
                )}
              </div>
            </DialogContent>
          </Dialog>
        </div>
      </CardContent>
    </Card>
  );
}
