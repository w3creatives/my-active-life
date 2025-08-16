import AppLayout from '@/layouts/app-layout';
import CreateTeam from '@/pages/teams/components/create-team';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import TeamMembers from './components/team-members';

export default function FollowPage() {
  const { team } = usePage<SharedData>().props;
  return (
    <AppLayout>
      <Head title="Teams" />
      <div className="flex flex-col gap-6 p-4">
        <h1 className="text-4xl font-normal">{team?.name || 'Join Or Create Your Team'}</h1>
        <TeamMembers />
        <CreateTeam />
      </div>
    </AppLayout>
  );
}
