import { useState, useEffect } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import axios from 'axios';

export interface UserPoint {
  id: string;
  date: string;
  miles: number;
  cumulative_miles: number;
  note?: string;
}

interface CalendarProps {
  date: Date;
  setDate: (date: Date) => void;
}

export function Calendar({ date, setDate }: CalendarProps) {
  const [selectedDate, setSelectedDate] = useState<Date | null>(null);
  const [userPoints, setUserPoints] = useState<UserPoint[]>([]);
  const [totalPoints, setTotalPoints] = useState<number>(0);
  const [eventInfo, setEventInfo] = useState<{ id: number; name: string } | null>(null);
  const [loading, setLoading] = useState<boolean>(true);

  // Get days in month
  const getDaysInMonth = (year: number, month: number) => {
    return new Date(year, month + 1, 0).getDate();
  };

  // Get day of week for first day of month (0 = Sunday, 6 = Saturday)
  const getFirstDayOfMonth = (year: number, month: number) => {
    return new Date(year, month, 1).getDay();
  };

  const currentYear = date.getFullYear();
  const currentMonth = date.getMonth();
  const daysInMonth = getDaysInMonth(currentYear, currentMonth);
  const firstDayOfMonth = getFirstDayOfMonth(currentYear, currentMonth);

  // Generate calendar days
  const days = [];
  // Add empty cells for days before the first day of the month
  for (let i = 0; i < firstDayOfMonth; i++) {
    days.push(null);
  }
  // Add days of the month
  for (let i = 1; i <= daysInMonth; i++) {
    days.push(new Date(currentYear, currentMonth, i));
  }

  // Navigate to previous month
  const prevMonth = () => {
    const newDate = new Date(currentYear, currentMonth - 1, 1);
    setDate(newDate);
  };

  // Navigate to next month
  const nextMonth = () => {
    const newDate = new Date(currentYear, currentMonth + 1, 1);
    setDate(newDate);
  };

  // Get points for a specific day
  const getPointsForDay = (day: Date) => {
    const formattedDate = formatDate(day);
    return userPoints.filter(point => point.date === formattedDate);
  };

  // Format date to display month and year
  const formatMonthYear = (date: Date) => {
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
  };

  // Format date as YYYY-MM-DD
  const formatDate = (date: Date) => {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  };

  // Check if a date is today
  const isToday = (date: Date) => {
    const today = new Date();
    return date.getDate() === today.getDate() &&
      date.getMonth() === today.getMonth() &&
      date.getFullYear() === today.getFullYear();
  };

  // Fetch user points data
  useEffect(() => {
    const fetchUserPoints = async () => {
      setLoading(true);
      try {
        const formattedDate = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}`;
        const response = await axios.get(route('user.points', { date: formattedDate }));
        setUserPoints(response.data.points);
        setTotalPoints(response.data.total);
        setEventInfo(response.data.event);
      } catch (error) {
        console.error('Error fetching user points:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchUserPoints();
  }, [currentYear, currentMonth]);

  return (
    <div className="w-full rounded-lg border bg-card text-card-foreground shadow-sm">
      <div className="flex items-center justify-between p-4">
        <div>
          <h2 className="text-lg font-semibold">{formatMonthYear(date)}</h2>
          {eventInfo && (
            <p className="text-sm text-muted-foreground">
              {eventInfo.name} - Total: {totalPoints} miles
            </p>
          )}
        </div>
        <div className="flex items-center space-x-1">
          <Button variant="outline" size="icon" onClick={prevMonth}>
            <ChevronLeft className="h-4 w-4" />
          </Button>
          <Button variant="outline" size="icon" onClick={nextMonth}>
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>
      </div>
      <div className="p-4">
        {loading ? (
          <div className="flex justify-center p-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
          </div>
        ) : (
          <div className="grid grid-cols-7 gap-1">
            {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((day) => (
              <div key={day} className="text-center text-sm font-medium text-muted-foreground">
                {day}
              </div>
            ))}
            {days.map((day, index) => (
              <div
                key={index}
                className={cn(
                  "aspect-square p-1 transition-colors",
                  day ? "cursor-pointer hover:bg-accent" : "",
                  day && isToday(day) ? "bg-accent/50" : "",
                  day && selectedDate && day.getDate() === selectedDate.getDate() &&
                  day.getMonth() === selectedDate.getMonth() ? "bg-accent" : ""
                )}
                onClick={() => day && setSelectedDate(day)}
              >
                {day && (
                  <div className="h-full w-full">
                    <div className="flex justify-end p-1 text-sm">
                      {day.getDate()}
                    </div>
                    <div className="space-y-1 p-1">
                      {getPointsForDay(day).map((point) => (
                        <div
                          key={point.id}
                          className="truncate text-center text-sm md:text-xl"
                          title={point.note || `${point.miles} miles (Total: ${point.cumulative_miles})`}
                        >
                          {Number(point.miles).toFixed(2)}±
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
