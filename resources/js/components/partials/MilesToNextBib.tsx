import { MilestoneRadialChart } from '@/components/partials/charts/MilestoneRadialChart';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useEffect, useState } from 'react';
import axios from 'axios';
import { Goal } from 'lucide-react';

interface NextMilestone {
  id: number;
  name: string;
  distance: number;
  description?: string;
  logo_image_url?: string;
  team_logo_image_url?: string;
}

interface MilestoneData {
  next_milestone: NextMilestone | null;
  previous_milestone: NextMilestone | null;
  current_distance: number;
  event_name: string;
}

export default function MilesToNextBib() {
  const [milestoneData, setMilestoneData] = useState<MilestoneData | null>(null);
  const [loading, setLoading] = useState(true);
  const fetchMilestoneData = async () => {
    try {
      const response = await axios.get(route('next.milestone'));
      setMilestoneData(response.data);
    } catch (error) {
      console.error('Error fetching milestone data:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchMilestoneData();

    const handler = () => {
      setLoading(true);
      fetchMilestoneData();
    };
    window.addEventListener('points-updated', handler as EventListener);
    return () => window.removeEventListener('points-updated', handler as EventListener);
  }, []);

  const getTitle = (eventName: string) => {
    if (eventName?.includes("Run The Year")) {
      return "Miles to Next Bib";
    } else if (eventName?.includes("Amerithon")) {
      return "Miles to Next Landmark";
    } else if (eventName?.includes("5K") || eventName?.includes("Challenge")) {
      return "Miles to Next Badge";
    } else {
      return "Miles to Next Bib";
    }
  };

  if (loading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="text-xl">Loading...</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="animate-pulse">
            <div className="h-[250px] bg-muted rounded-full mx-auto aspect-square max-w-[250px]"></div>
          </div>
        </CardContent>
      </Card>
    );
  }

  if (!milestoneData?.next_milestone) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="text-xl"><Goal /> {getTitle(milestoneData?.event_name || '')}</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="text-center py-8">
            <p className="text-muted-foreground">All milestones completed!</p>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="leading-none font-semibold flex items-center justify-center gap-2"><Goal /> {getTitle(milestoneData.event_name)}</CardTitle>
      </CardHeader>
      <CardContent>
        <MilestoneRadialChart
          current={milestoneData.current_distance}
          nextMilestone={milestoneData.next_milestone.distance}
          previousMilestone={milestoneData.previous_milestone?.distance || 0}
        />
      </CardContent>
    </Card>
  );
}
