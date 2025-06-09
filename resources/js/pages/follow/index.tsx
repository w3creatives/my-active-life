import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import FollowTeam from '@/pages/follow/components/follow-team';
import FollowingParticipants from '@/pages/follow/components/following-participants';
import FollowingTeams from '@/pages/follow/components/following-teams';
import FollowParticipant from '@/pages/follow/components/follow-participant';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Follow',
    href: route('follow'),
  },
];

interface Props {
  userFollowings: any;
  teamFollowings: any;
  users: any;
  teams: any;
}

export default function FollowPage({userFollowings, teamFollowings, users, teams} : Props) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Follow" />
      <div className="flex flex-col gap-6 p-4">
        <FollowingParticipants participants={userFollowings} />
        <FollowingTeams teams={teamFollowings} />
        <FollowParticipant users={users} />
        <FollowTeam teams={teams} />
      </div>
    </AppLayout>
  );
}
