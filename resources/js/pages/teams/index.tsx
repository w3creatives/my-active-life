import AppLayout from '@/layouts/app-layout';
import CreateTeam from '@/pages/teams/components/create-team';
import { type SharedData } from '@/types';
import { Head, usePage, Link } from '@inertiajs/react';
import TeamMembers from './components/team-members';
import InviteMembers from './components/invite-members';
import { Button } from '@/components/ui/button';
import { Mail, Users } from 'lucide-react';

export default function FollowPage() {
  const { team } = usePage<SharedData>().props;
  const teamData = team as { name?: string } | null;
  
  return (
    <AppLayout>
      <Head title="Teams" />
      <div className="flex flex-col gap-6 p-4">
        <div className="flex items-center justify-between">
          <h1 className="text-4xl font-normal">{teamData?.name || 'Join Or Create Your Team'}</h1>
          {teamData && (
            <div className="flex gap-2">
              <Link href={route('teams.invites')}>
                <Button variant="outline" className="flex items-center gap-2">
                  <Mail className="h-4 w-4" />
                  View Invites
                </Button>
              </Link>
            </div>
          )}
        </div>
        <TeamMembers />
        <CreateTeam />
        <InviteMembers />
      </div>
    </AppLayout>
  );
}
