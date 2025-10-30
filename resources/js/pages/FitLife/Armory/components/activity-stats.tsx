import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Calendar, Heart, Star, Target } from 'lucide-react';

interface ActivityStatsProps {
  total: number;
  completed: number;
  scheduled: number;
  totalPoints: number;
  completionPercentage: number;
}

export default function ActivityStats({
  total,
  completed,
  scheduled,
  totalPoints,
  completionPercentage,
}: ActivityStatsProps) {
  const stats = [
    {
      title: 'Total Activities',
      value: total,
      icon: Target,
      iconColor: 'text-primary',
    },
    {
      title: 'Completed',
      value: completed,
      subtext: `${completionPercentage.toFixed(1)}% completion rate`,
      icon: Star,
      iconColor: 'text-green-500',
      valueColor: 'text-green-600',
    },
    {
      title: 'Scheduled',
      value: scheduled,
      icon: Calendar,
      iconColor: 'text-blue-500',
      valueColor: 'text-blue-600',
    },
    {
      title: 'Total Points',
      value: totalPoints.toLocaleString(),
      icon: Heart,
      iconColor: 'text-pink-500',
      valueColor: 'text-pink-600',
    },
  ];

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {stats.map((stat, index) => (
        <Card key={index}>
          <CardHeader className="pb-3">
            <CardTitle className="flex items-center gap-2 text-sm font-medium">
              <stat.icon className={`h-4 w-4 ${stat.iconColor}`} />
              {stat.title}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className={`text-2xl font-bold ${stat.valueColor || ''}`}>{stat.value}</div>
            {stat.subtext && <p className="text-muted-foreground text-xs">{stat.subtext}</p>}
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
