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

export default function Profile() {

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Trophy Case" />
      <PageContent>
        <div className="space-y-8">
          <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <div className="mb-2 flex items-center gap-3">
                <h1 className="text-4xl font-bold">Trophy Case</h1>
              </div>
              <p className="text-muted-foreground text-xl">
                Your achievements in <span className="text-foreground font-semibold">{trophyData.event.name}</span>
              </p>
            </div>
          </div>
        </div>
      </PageContent>
    </AppLayout>
  );
}
