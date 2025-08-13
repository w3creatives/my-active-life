import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import CreateTeam from '@/pages/teams/components/create-team';

export default function FollowPage() {
    return (
      <AppLayout>
        <Head title="Teams" />
        <div className="flex flex-col gap-6 p-4">
          <h1 className="text-4xl font-normal">Join Or Create Your Team</h1>
          <CreateTeam/>
        </div>
      </AppLayout>
    );
}
