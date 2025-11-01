import PageContent from '@/components/atoms/page-content';
import ContentHeader from '@/components/molecules/ContentHeader';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { User, Users } from 'lucide-react';
import { toast } from 'sonner';
import Last30days from '@/pages/stats/components/last30days';
import PersonalBests from '@/pages/stats/components/PersonalBests';
import MileageByActivityType from '@/pages/stats/components/MileageByActivityType';
import Heroism from '@/pages/stats/components/Heroism';
import FavoriteQuests from '@/pages/stats/components/FavoriteQuests';
import QuestsCalendar from '@/pages/stats/components/QuestsCalendar';

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

interface ActivityTypeData {
  name: string;
  miles: number;
  percentage: number;
  color: string;
}

interface MileageData {
  data: ActivityTypeData[];
  totalMiles: number;
}

interface HeroismData {
  total_registrations: number;
  total_completed: number;
  completion_percentage: number;
}

interface HeroismResponse {
  lifetime: HeroismData;
  last_30_days: HeroismData;
}

interface FavoriteQuestData {
  name: string;
  value: number;
  percentage: number;
}

interface FavoriteQuestsResponse {
  data: FavoriteQuestData[];
  total_registrations: number;
}

interface QuestDayData {
  week: number;
  day: number;
  points: number;
  date: string;
  activity_name: string | null;
  registration_id: number | null;
}

interface QuestsCalendarResponse {
  data: QuestDayData[];
}

export default function StatsIndex() {
  const { auth, team } = usePage().props;
  const teamData = team as { name?: string } | null;
  const [dataFor, setDataFor] = useState('you');
  const [mileageData, setMileageData] = useState<MileageData>({ data: [], totalMiles: 0 });
  const [heroismData, setHeroismData] = useState<HeroismResponse | null>(null);
  const [favoriteQuestsData, setFavoriteQuestsData] = useState<FavoriteQuestsResponse>({ data: [], total_registrations: 0 });
  const [questsCalendarData, setQuestsCalendarData] = useState<QuestDayData[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchMileageData();
    fetchHeroismData();
    fetchFavoriteQuestsData();
    fetchQuestsCalendarData();
  }, []);

  const fetchMileageData = async () => {
    try {
      setLoading(true);
      const response = await fetch(route('webapi.mileage-by-activity-type'), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
      });

      const result = await response.json();

      if (result.success) {
        setMileageData(result.data);
      } else {
        toast.error('Failed to load mileage data');
      }
    } catch (error) {
      console.error('Error fetching mileage data:', error);
      toast.error('Failed to load mileage data');
    } finally {
      setLoading(false);
    }
  };

  const fetchHeroismData = async () => {
    try {
      const response = await fetch(route('webapi.heroism-data'), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
      });

      const result = await response.json();

      if (result.success) {
        setHeroismData(result.data);
      } else {
        toast.error('Failed to load heroism data');
      }
    } catch (error) {
      console.error('Error fetching heroism data:', error);
      toast.error('Failed to load heroism data');
    }
  };

  const fetchFavoriteQuestsData = async () => {
    try {
      const response = await fetch(route('webapi.favorite-quests'), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
      });

      const result = await response.json();

      if (result.success) {
        setFavoriteQuestsData(result.data);
      } else {
        toast.error('Failed to load favorite quests data');
      }
    } catch (error) {
      console.error('Error fetching favorite quests data:', error);
      toast.error('Failed to load favorite quests data');
    }
  };

  const fetchQuestsCalendarData = async () => {
    try {
      const response = await fetch(route('webapi.quests-calendar'), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
      });

      const result = await response.json();

      if (result.success) {
        setQuestsCalendarData(result.data.data);
      } else {
        toast.error('Failed to load quests calendar data');
      }
    } catch (error) {
      console.error('Error fetching quests calendar data:', error);
      toast.error('Failed to load quests calendar data');
    }
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Bard's Tale" />
      <PageContent>
        <div className="flex justify-between gap-5 mb-6">
          <ContentHeader title={`${auth.user.display_name}'s Bard's Tale`} />
        </div>

        <div className="grid grid-cols-1 gap-6">
          <MileageByActivityType
            dataFor={dataFor}
            data={mileageData.data}
            totalMiles={mileageData.totalMiles}
          />
        </div>

        <div className="flex gap-5">
          <div className="w-2/6">
            <PersonalBests dataFor="you" />
          </div>
        </div>

        <div className="grid grid-cols-1 gap-6">
          <Last30days dataFor="you" />
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <Heroism lifetime={heroismData?.lifetime} last30Days={heroismData?.last_30_days} />
        </div>

        <div className="grid grid-cols-1 gap-6">
          <FavoriteQuests data={favoriteQuestsData.data} totalRegistrations={favoriteQuestsData.total_registrations} />
        </div>

        <div className="grid grid-cols-1 gap-6">
          <QuestsCalendar data={questsCalendarData} />
        </div>

        {/*
        <div className="grid grid-cols-1 gap-6">
          <PersonalBests dataFor="you" />
        </div>

        <div className="grid grid-cols-1 gap-6">
          <MileageByActivityType
            dataFor={dataFor}
            data={mileageData.data}
            totalMiles={mileageData.totalMiles}
          />
        </div>
        */}
      </PageContent>
    </AppLayout>
  );
}
