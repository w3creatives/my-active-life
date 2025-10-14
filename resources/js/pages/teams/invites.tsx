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
        <TeamInvites pendingInvites={pendingInvites} team={team} />
      </div>
    </AppLayout>
  );
}
