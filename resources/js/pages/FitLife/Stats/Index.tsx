import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import PageContent from '@/components/atoms/page-content';
import ContentHeader from '@/components/molecules/ContentHeader';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: route('dashboard'),
  },
  {
    title: "Bard's Tale",
    href: route('fit-life-activities.stats'),
  },
];

export default function BlankPage() {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Bard's Tale" />
      <PageContent>
        <ContentHeader title="Bard's Tale" />
      </PageContent>
    </AppLayout>
  );
}
