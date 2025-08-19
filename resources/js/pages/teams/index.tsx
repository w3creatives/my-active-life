import AppLayout from '@/layouts/app-layout';
import CreateTeam from '@/pages/teams/components/create-team';
import { type SharedData } from '@/types';
import { Head, usePage, Link } from '@inertiajs/react';
import TeamMembers from './components/team-members';
import InviteMembers from './components/invite-members';
import { Button } from '@/components/ui/button';
import { Mail, Users, ChartPie } from 'lucide-react';
import PageContent from '@/components/atoms/page-content';
import AdminControls from './components/admin-controls';

export default function FollowPage() {
  const { team } = usePage<SharedData>().props;
  const teamData = team as { name?: string } | null;
  
  return (
    <AppLayout>
      <Head title="Teams" />
      <PageContent>
        <div className="flex justify-between items-center">
          <h1 className="font-normal text-4xl">{teamData?.name || 'Join Or Create Your Team'}</h1>
          {teamData && (
            <div className="flex gap-2">
              <Link href={route('teams.invites')}>
                <Button variant="outline" className="flex items-center gap-2">
                  <Mail className="w-4 h-4" />
                  View Invites
                </Button>
              </Link>
              <Link href={route('teams.invites')}>
                <Button variant="default" className="flex items-center gap-2">
                  <ChartPie className="w-4 h-4" />
                  Show Stats
                </Button>
              </Link>
            </div>
          )}
        </div>
        <CreateTeam />
        {teamData && <TeamMembers />}
        {teamData && <InviteMembers />}
        {/* Admin Controls Section - Only shown for team owners */}
        {teamData && <AdminControls />}
      </PageContent>
    </AppLayout>
  );
}
