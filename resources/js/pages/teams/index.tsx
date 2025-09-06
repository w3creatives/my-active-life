import PageContent from '@/components/atoms/page-content';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import CreateTeam from '@/pages/teams/components/create-team';
import TeamToJoin from '@/pages/teams/components/team-to-join';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ChartPie, Mail } from 'lucide-react';
import AdminControls from './components/admin-controls';
import InviteMembers from './components/invite-members';
import TeamMembers from './components/team-members';

export default function FollowPage() {
  const { team } = usePage<SharedData>().props;
  const teamData = team as { name?: string } | null;

  return (
    <AppLayout>
      <Head title="Teams" />
      <PageContent>
        <div className="flex items-center justify-between">
          <h1 className="text-4xl font-normal">{teamData?.name || 'Join Or Create Your Team'}</h1>
          {teamData && (
            <div className="flex gap-2">
              <Link href={route('teams.invites')}>
                <Button variant="outline-primary" className="flex items-center gap-2">
                  <Mail className="h-4 w-4" />
                  View Invites
                </Button>
              </Link>
              <Link href={route('stats')}>
                <Button variant="default" className="flex items-center gap-2">
                  <ChartPie className="h-4 w-4" />
                  Show Stats
                </Button>
              </Link>
            </div>
          )}
        </div>
        <CreateTeam />
        {!teamData && <TeamToJoin />}
        {teamData && <TeamMembers />}
        {teamData && <InviteMembers />}
        {/* Admin Controls Section - Only shown for team owners */}
        {teamData && <AdminControls />}
      </PageContent>
    </AppLayout>
  );
}
