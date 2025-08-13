import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import PageContent from '@/components/atoms/page-content';
import { useState, useEffect } from 'react';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: route('dashboard')
  },
  {
    title: 'Teams',
    href: route('teams')
  }
];
export default function Teams() {
  const [teams, setTeams] = useState([]);

  useEffect(() => {
    const fetchTeams = async () => {
      try {
        const response = await axios.get(route('user.event.details', { type: 'own' }));
        setTeams(response.data.teams);
      } catch (err) {
        console.error('Error fetching teams:', err);
      }
    };

    fetchTeams();
  }, []);

  console.log(teams);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Teams" />
      <PageContent>
        {teams.length === 0 ? (
          <div className="text-center">No teams found.</div>
        ) : (
          <ul className="space-y-2">
            {teams.map((team) => (
              <li key={team.id} className="text-lg font-semibold">
                {team.name}
              </li>
            ))}
          </ul>
        )}
      </PageContent>
    </AppLayout>
  );
}
