import { Calendar } from '@/components/ui/calendar';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: route('dashboard'),
    },
];

export default function Dashboard() {
    const [date, setDate] = useState<Date>(new Date());

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Home" />
            <div className="flex flex-col gap-6 p-4">
                <Calendar date={date} setDate={setDate} />
            </div>
        </AppLayout>
    );
}
