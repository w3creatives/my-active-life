import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { type SharedData } from '@/types';
import { router, useForm, usePage } from '@inertiajs/react';
import axios from 'axios';
import { AlertTriangle, Crown, Search, Users } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';
import { toast } from 'sonner';

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
  const { team, auth } = usePage<SharedData>().props;
  const teamData = team as any; // Type assertion to avoid TypeScript errors
  const [isTransferDialogOpen, setIsTransferDialogOpen] = useState(false);
  const [teamMembers, setTeamMembers] = useState<Pagination<TeamMember> | null>(null);
  const [loadingMembers, setLoadingMembers] = useState(false);
  const [selectedMemberId, setSelectedMemberId] = useState<number | null>(null);
  const [searchMember, setSearchMember] = useState('');

  const { processing } = useForm();

  // Check if user is team owner - moved after all hooks to prevent React hooks error
  const isTeamOwner = teamData && teamData.owner_id === auth.user.id;

  const fetchTeamMembers = async (searchTerm: string = '') => {
    try {
      setLoadingMembers(true);
      const params = new URLSearchParams();
      params.append('teamId', teamData.id);
      params.append('perPage', '100'); // Get all members for transfer
      if (searchTerm) {
        params.append('searchUser', searchTerm);
      }

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

    router.post(
      route('teams.dissolve'),
      {
        team_id: teamData.id,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Team has been dissolved successfully');
          // Redirect to teams page
          router.visit(route('teams'));
        },
        onError: (errors: any) => {
          toast.error(errors.error || 'Failed to dissolve team');
        },
      },
    );
  };

  const handleTransferAdminRole = () => {
    if (!selectedMemberId) {
      toast.error('Please select a team member to transfer admin role to');
      return;
    }

    router.post(
      route('teams.transfer-admin-role'),
      {
        team_id: teamData.id,
        member_id: selectedMemberId,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Team admin role has been transferred successfully');
          setIsTransferDialogOpen(false);
          setSelectedMemberId(null);
          // Refresh the page to update the UI
          router.reload();
        },
        onError: (errors: any) => {
          toast.error(errors.error || 'Failed to transfer admin role');
        },
      },
    );
  };

  const openTransferDialog = () => {
    setIsTransferDialogOpen(true);
    fetchTeamMembers();
  };

  // Debounced search trigger
  useEffect(() => {
    if (isTransferDialogOpen) {
      const timeout = setTimeout(() => {
        fetchTeamMembers(searchMember);
      }, 500);
      return () => clearTimeout(timeout);
    }
  }, [searchMember, isTransferDialogOpen]);

  // Only show admin controls if user is the team owner
  if (!isTeamOwner) {
    return null;
  }

  return (
    <Card className="border-red-200 bg-red-50">
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-red-800">
          <AlertTriangle className="h-5 w-5" />
          Other Admin Controls
        </CardTitle>
        <CardDescription className="text-red-700">These actions are irreversible and will affect all team members.</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex flex-col gap-3 sm:flex-row">
          <Button variant="destructive" onClick={handleDissolveTeam} disabled={processing} className="flex items-center gap-2">
            <AlertTriangle className="h-4 w-4" />
            Dissolve Team
          </Button>

          <Dialog open={isTransferDialogOpen} onOpenChange={setIsTransferDialogOpen}>
            <DialogTrigger asChild>
              <Button
                variant="outline"
                onClick={openTransferDialog}
                className="flex items-center gap-2 border-orange-300 text-orange-700 hover:bg-orange-50"
              >
                <Crown className="h-4 w-4" />
                Transfer Admin Role
              </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
              <DialogHeader>
                <DialogTitle className="flex items-center gap-2">
                  <Crown className="h-5 w-5" />
                  Transfer Admin Role
                </DialogTitle>
                <DialogDescription>
                  Select a team member to transfer the admin role to. You will no longer be the team admin after this action.
                </DialogDescription>
              </DialogHeader>

              <div className="space-y-4">
                {/* Search Input */}
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                  <Input
                    type="search"
                    placeholder="Search team members..."
                    className="pl-10"
                    value={searchMember}
                    onChange={(e) => setSearchMember(e.target.value)}
                  />
                </div>

                {loadingMembers ? (
                  <div className="py-4 text-center">
                    <div className="mx-auto h-8 w-8 animate-spin rounded-full border-b-2 border-gray-900"></div>
                    <p className="mt-2 text-sm text-gray-600">Loading team members...</p>
                  </div>
                ) : teamMembers && teamMembers.data.length > 0 ? (
                  <div className="max-h-60 space-y-2 overflow-y-auto">
                    {teamMembers.data
                      .filter((member) => member.id !== teamData.owner_id) // Filter out current admin
                      .map((member) => (
                        <div
                          key={member.id}
                          className={`flex cursor-pointer items-center justify-between rounded-lg border p-3 transition-colors ${
                            selectedMemberId === member.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'
                          }`}
                          onClick={() => setSelectedMemberId(member.id)}
                        >
                          <div className="flex items-center gap-3">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100">
                              <Users className="h-4 w-4 text-gray-600" />
                            </div>
                            <div>
                              <p className="text-sm font-medium">{member.name}</p>
                              <p className="text-xs text-gray-500">{member.miles} miles</p>
                            </div>
                          </div>
                          {selectedMemberId === member.id && <Crown className="h-4 w-4 text-blue-600" />}
                        </div>
                      ))}
                  </div>
                ) : (
                  <div className="py-4 text-center">
                    <p className="text-sm text-gray-600">
                      {teamMembers && teamMembers.data.length > 0 
                        ? 'No other team members found to transfer admin role to' 
                        : 'No team members found'
                      }
                    </p>
                  </div>
                )}

                {selectedMemberId && (
                  <div className="flex gap-2 pt-4">
                    <Button onClick={handleTransferAdminRole} disabled={processing} className="flex-1">
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
