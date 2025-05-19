import { Calendar } from '@/components/ui/calendar';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: route('dashboard'),
    },
];

export default function Dashboard() {
    const { auth } = usePage<SharedData>().props;
    const [date, setDate] = useState<Date>(new Date());

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Home" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-4xl font-normal">{auth.user.display_name}'s Amerithon Journey</h1>
                <Calendar date={date} setDate={setDate} />
            </div>
        </AppLayout>
    );
}
