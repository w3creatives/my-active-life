import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { Crown, Search } from 'lucide-react';
import { useEffect, useState } from 'react';

interface MemberStatProps {
    className?: string;
    dataFor?: string;
}

interface AccomplishmentProps {
  accomplishment: number;
  date: string;
}

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const MemberStatRowSkeleton = () => (
    <div className="flex flex-wrap border-b p-4 text-sm lg:items-center">
        <div className="flex w-1/7 items-center gap-3 lg:w-1/7">
            <Skeleton className="h-10 w-10 rounded-full" />
            <div className="space-y-1">
                <Skeleton className="h-4 w-24" />
                <Skeleton className="h-3 w-32" />
            </div>
        </div>

        <div className="text-center w-1/7 lg:w-1/7">
            <Skeleton className="h-4 w-12" />
        </div>
        <div className="text-center mt-2 w-full lg:mt-0 lg:w-1/7">
            <Skeleton className="h-8 w-16" />
        </div>
        <div className="text-center mt-2 w-full lg:mt-0 lg:w-1/7">
            <Skeleton className="h-8 w-16" />
        </div>
        <div className="text-center mt-2 w-full lg:mt-0 lg:w-1/7">
            <Skeleton className="h-8 w-16" />
        </div>
        <div className="text-center mt-2 w-full lg:mt-0 lg:w-1/7">
            <Skeleton className="h-8 w-16" />
        </div>
        <div className="text-center mt-2 w-full lg:mt-0 lg:w-1/7">
            <Skeleton className="h-8 w-16" />
        </div>
    </div>
);


function Accomplishment({ accomplishment, date }: AccomplishmentProps) {
  console.log(accomplishment);
  return (
    <div className="w-1/7 lg:w-1/7">
      <div className="text-center">
        <div className="text-primary text-lg font-semibold">{accomplishment}</div>
        <div className="text-muted-foreground text-xs">{formatDate(date)}</div>
      </div>
    </div>
  );
}

export default function MemberStat({dateFor}: MemberStatProps) {
  const { team, auth } = usePage<SharedData>().props;
  const teamData = team as any; // Type assertion to avoid TypeScript errors

  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchMemberStats = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      params.append('teamId', teamData?.id);

      const response = await axios.get(route('stat.team.members') + '?' + params.toString());
      setUsers(response.data);
    } catch (err) {
      setError('Failed to load available team members stats');
      console.error('Error fetching available team members stats:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
      fetchMemberStats();
  }, [dateFor]);

  // Skeleton component for individual user rows

  return (
    <Card className="pb-0">
      <CardHeader>
        <CardTitle className="text-lg">{ loading?<Skeleton className="h-4 12" />:"Team Members Bests"}</CardTitle>
        <CardDescription className="text-sm">{ loading?<Skeleton className="h-4 w-full" />:"Team Members best performances across different time periods"}</CardDescription>
      </CardHeader>

      <CardContent className="space-y-6">
          <div className="text-md font-semibold grid grid-cols-7 border-b px-4 py-2 text-sm font-medium md:grid-cols-7">
            <div>{ loading?<Skeleton className="h-4 w-12" />:"Member Name"}</div>
            <div className="text-center">{ loading?<Skeleton className="h-4 w-12" />:"Best Day"}</div>
            <div className="text-center">{ loading?<Skeleton className="h-4 w-12" />:"Best Week"}</div>
            <div className="text-center">{ loading?<Skeleton className="h-4 w-12" />:"Best Month"}</div>
            <div className="text-center">{ loading?<Skeleton className="h-4 w-12" />:"Today"}</div>
            <div className="text-center">{ loading?<Skeleton className="h-4 w-12" />:"This Week"}</div>
            <div className="text-center">{ loading?<Skeleton className="h-4 w-12" />:"This Month"}</div>
          </div>
          {loading ? (
            // Show skeleton rows while loading
            <>
              <MemberStatRowSkeleton />
              <MemberStatRowSkeleton />
              <MemberStatRowSkeleton />
            </>
          ) : error ? (
            <div className="p-8 text-center text-red-500">{error}</div>
          ) : !users || users.length === 0 ? (
            <div className="text-muted-foreground p-8 text-center">No record found.</div>
          ) : (
            users.map((member) => (
              <div key={member.id} className="flex flex-wrap border-b p-4 text-sm lg:items-center">
                <div className="flex w-1/7 items-center gap-3 lg:w-1/7">
                  <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 font-bold text-gray-500">
                    {member.first_name.charAt(0)}
                  </div>
                  <div>
                    <div className="flex items-center gap-2 font-medium">
                      {member.display_name}
                      {member.id === teamData?.owner_id && <Crown className="h-4 w-4 text-yellow-600" title="Team Admin" />}
                    </div>
                  </div>
                </div>

                <Accomplishment {...member.achievement.best_day} />
                <Accomplishment {...member.achievement.best_week} />
                <Accomplishment {...member.achievement.best_month} />
                <Accomplishment {...member.achievement.current_day} />
                <Accomplishment {...member.achievement.current_week} />
                <Accomplishment {...member.achievement.current_month} />
              </div>
            ))
          )}
      </CardContent>
    </Card>
  );
}
