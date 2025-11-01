import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Calendar } from 'lucide-react';
import { useMemo } from 'react';

interface QuestDayData {
  week: number;
  day: number;
  points: number;
  date: string;
  activity_name: string | null;
  registration_id: number | null;
}

interface QuestsCalendarProps {
  data?: QuestDayData[];
  className?: string;
}

const dayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const monthLabels = [
  'Jan',
  'Feb',
  'Mar',
  'Apr',
  'May',
  'Jun',
  'Jul',
  'Aug',
  'Sep',
  'Oct',
  'Nov',
  'Dec',
];

// Get color based on points (GitHub-style)
const getColor = (points: number) => {
  if (points === 0) return 'bg-muted';
  if (points <= 25) return 'bg-green-200 dark:bg-green-900';
  if (points <= 50) return 'bg-green-300 dark:bg-green-800';
  if (points <= 75) return 'bg-green-400 dark:bg-green-700';
  return 'bg-green-500 dark:bg-green-600';
};

export default function QuestsCalendar({ data = [], className = '' }: QuestsCalendarProps) {
  // Organize data by week and track month changes
  const { calendarData, monthBreaks } = useMemo(() => {
    if (!data || data.length === 0) return { calendarData: {}, monthBreaks: new Set<number>() };

    const organized: Record<number, QuestDayData[]> = {};
    const breaks = new Set<number>();
    let lastMonth = -1;

    data.forEach((day) => {
      if (!organized[day.week]) {
        organized[day.week] = [];
      }
      organized[day.week].push(day);

      // Track month changes
      const date = new Date(day.date);
      const currentMonth = date.getMonth();
      if (lastMonth !== -1 && currentMonth !== lastMonth && day.day === 0) {
        breaks.add(day.week);
      }
      lastMonth = currentMonth;
    });

    // Sort days within each week
    Object.keys(organized).forEach((weekKey) => {
      organized[Number(weekKey)].sort((a, b) => a.day - b.day);
    });

    return { calendarData: organized, monthBreaks: breaks };
  }, [data]);

  const weeks = Object.keys(calendarData)
    .map(Number)
    .sort((a, b) => a - b);

  const hasData = data.length > 0;

  // Get month positions for labels
  const getMonthPositions = () => {
    const positions: { month: string; index: number }[] = [];
    let lastMonth = -1;

    weeks.forEach((week, index) => {
      const firstDayOfWeek = calendarData[week]?.[0];
      if (firstDayOfWeek && firstDayOfWeek.date) {
        const date = new Date(firstDayOfWeek.date);
        const month = date.getMonth();

        if (month !== lastMonth) {
          positions.push({ month: monthLabels[month], index });
          lastMonth = month;
        }
      }
    });

    return positions;
  };

  const monthPositions = getMonthPositions();

  return (
    <Card className={`${className}`}>
      <CardHeader className="pb-3">
        <div className="flex items-center gap-2">
          <Calendar className="text-primary h-6 w-6" />
          <CardTitle className="text-xl">Quests</CardTitle>
        </div>
        <CardDescription>Your quest completion activity this year</CardDescription>
      </CardHeader>
      <CardContent className="pb-4">
        {!hasData ? (
          <div className="flex h-48 items-center justify-center text-center">
            <div>
              <Calendar className="text-muted-foreground/50 mx-auto mb-4 h-12 w-12" />
              <p className="text-muted-foreground mb-2">No quest data available</p>
              <p className="text-muted-foreground/70 text-sm">Complete quests to see your activity calendar</p>
            </div>
          </div>
        ) : (
          <div className="w-full overflow-x-auto">
            {/* Month labels */}
            <div className="flex mb-2 pl-10">
              {monthPositions.map((pos, idx) => {
                const cellWidth = 14; // dot width (3.5 * 4)
                const gapWidth = 4; // gap-1 = 4px
                const totalCellWidth = cellWidth + gapWidth;
                const left = pos.index * totalCellWidth;

                return (
                  <div
                    key={pos.month}
                    className="text-xs font-medium text-muted-foreground absolute"
                    style={{ left: `${left}px` }}
                  >
                    {pos.month}
                  </div>
                );
              })}
            </div>

            <div className="flex gap-2 relative">
              {/* Day labels - ALL DAYS */}
              <div className="flex flex-col gap-1">
                {dayLabels.map((label) => (
                  <div key={label} className="h-3.5 w-8 flex items-center justify-end text-[10px] text-muted-foreground">
                    {label}
                  </div>
                ))}
              </div>

              {/* Calendar grid */}
              <TooltipProvider>
                <div className="flex gap-1">
                  {weeks.map((week) => (
                    <div key={week} className="flex flex-col gap-1">
                      {calendarData[week]?.map((day) => (
                        <Tooltip key={`${week}-${day.day}`} delayDuration={100}>
                          <TooltipTrigger asChild>
                            <div
                              className={`h-3.5 w-3.5 rounded-sm cursor-pointer transition-all hover:ring-2 hover:ring-primary hover:scale-110 ${getColor(
                                day.points
                              )}`}
                            />
                          </TooltipTrigger>
                          <TooltipContent side="top" className="max-w-xs">
                            <div className="text-xs">
                              <div className="font-semibold">
                                {new Date(day.date).toLocaleDateString('en-US', {
                                  weekday: 'short',
                                  month: 'short',
                                  day: 'numeric',
                                  year: 'numeric',
                                })}
                              </div>
                              {day.activity_name && <div className="mt-1.5 text-muted-foreground">{day.activity_name}</div>}
                            </div>
                          </TooltipContent>
                        </Tooltip>
                      ))}
                    </div>
                  ))}
                </div>
              </TooltipProvider>
            </div>

            {/* Legend */}
            <div className="flex items-center gap-2 mt-3 text-xs text-muted-foreground pl-10">
              <span>Less</span>
              <div className="flex gap-1">
                <div className="h-3.5 w-3.5 rounded-sm bg-muted border" />
                <div className="h-3.5 w-3.5 rounded-sm bg-green-200 dark:bg-green-900" />
                <div className="h-3.5 w-3.5 rounded-sm bg-green-300 dark:bg-green-800" />
                <div className="h-3.5 w-3.5 rounded-sm bg-green-400 dark:bg-green-700" />
                <div className="h-3.5 w-3.5 rounded-sm bg-green-500 dark:bg-green-600" />
              </div>
              <span>More</span>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
