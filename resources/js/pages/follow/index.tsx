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

export default function FollowPage() {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Follow" />
      <div className="flex flex-col gap-6 p-4">
        <h1 className="text-4xl font-normal">Follow</h1>
        <FollowingParticipants />
        <FollowingTeams />
        <FollowParticipant />
        <FollowTeam />
      </div>
    </AppLayout>
  );
}
