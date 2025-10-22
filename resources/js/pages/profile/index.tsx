import PageContent from '@/components/atoms/page-content';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';


const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: route('dashboard'),
  },
  {
    title: 'Profile',
    href: route('profile'),
  },
];

export default function Profile() {

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Profile" />
      <PageContent>
        <Heading title="Profile" />
      </PageContent>
    </AppLayout>
  );
}
