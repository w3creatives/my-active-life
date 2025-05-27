import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Stats',
        href: route('stats'),
    },
];

export default function Stats() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Stats" />
            <div className="flex flex-col gap-6 p-4">
                Stats Page
            </div>
        </AppLayout>
    );
}
