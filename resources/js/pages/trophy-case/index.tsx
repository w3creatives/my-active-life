import PageContent from '@/components/atoms/page-content';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: route('dashboard'),
  },
  {
    title: 'Trophy Case',
    href: route('trophy-case'),
  },
];

export default function TrophyCase() {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Trophy Case" />
      <PageContent>
        <h1 className="text-4xl font-normal">Trophy Case</h1>
      </PageContent>
    </AppLayout>
  );
}
