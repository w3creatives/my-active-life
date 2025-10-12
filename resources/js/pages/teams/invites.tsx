import AppLayout from '@/layouts/app-layout';
import TeamInvites from '@/pages/teams/components/team-invites';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { ChevronLeft } from 'lucide-react';

export default function TeamInvitesPage() {
  const { team, pendingInvites } = usePage<SharedData>().props;

  return (
    <AppLayout>
      <Head title="Team Invites" />
      <div className="flex flex-col gap-6 p-4">
        <div className="flex items-center justify-between">
          <Heading title='Team Invites' description='Manage pending invitations for RTE R' />
          <Link href={route('teams')}>
            <Button className="flex items-center gap-2">
              <ChevronLeft />
              Back to Teams
            </Button>
          </Link>
        </div>
        <TeamInvites pendingInvites={pendingInvites} team={team} />
      </div>
    </AppLayout>
  );
}
