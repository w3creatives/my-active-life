import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import PageContent from '@/components/atoms/page-content';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: route('dashboard'),
    },
    {
        title: 'Blank Page',
        href: '',
    },
];

export default function BlankPage() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Blank Page" />
            <PageContent>
                <h1>Blank Page</h1>
            </PageContent>
        </AppLayout>
    );
}
