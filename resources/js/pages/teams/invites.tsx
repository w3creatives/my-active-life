import AppLayout from '@/layouts/app-layout';
import TeamInvites from '@/pages/teams/components/team-invites';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

export default function TeamInvitesPage() {
  const { team, pendingInvites } = usePage<SharedData>().props;

  return (
    <AppLayout>
      <Head title="Team Invites" />
      <div className="flex flex-col gap-6 p-4">
        <h1 className="text-4xl font-normal">Team Invites</h1>
        <TeamInvites pendingInvites={pendingInvites} team={team} />
      </div>
    </AppLayout>
  );
}
