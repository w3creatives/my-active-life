import React, { useState, useEffect } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import axios from 'axios';
import { PointsDetailModal } from '@/components/points-detail-modal';
import { Skeleton } from '@/components/ui/skeleton';

export interface UserPoint {
  id: string;
  date: string;
  amount: number;
  cumulative_miles: number;
  note?: string;
  modality?: string;
}

interface CalendarProps {
  date: Date;
  setDate: (date: Date) => void;
  disableFuture?: boolean;
}

export function Calendar({ date, setDate, disableFuture = true }: CalendarProps) {
  const [selectedDate, setSelectedDate] = useState<Date | null>(null);
  const [userPoints, setUserPoints] = useState<UserPoint[]>([]);
  const [eventInfo, setEventInfo] = useState<{ id: number; name: string } | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [isModalOpen, setIsModalOpen] = useState<boolean>(false);
  const [activeModality, setActiveModality] = useState<string>("all");

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
  // Add empty cells for days after the last day of the month to complete the last week
  const remainingCells = 7 - (days.length % 7);
  if (remainingCells < 7) {
    for (let i = 0; i < remainingCells; i++) {
      days.push(null);
    }
  }

  // After the days array creation, add calculation for weekly totals
  const weeks = [];
  let currentWeek = [];
  days.forEach((day) => {
    currentWeek.push(day);
    if (currentWeek.length === 7) {
      weeks.push(currentWeek);
      currentWeek = [];
    }
  });
  if (currentWeek.length > 0) {
    weeks.push(currentWeek);
  }

  // Add function to calculate weekly totals
  const getWeeklyTotal = (week: (Date | null)[]) => {
    return week
      .filter((day): day is Date => day !== null)
      .reduce((total, day) => {
        const dayPoints = getPointsForDay(day);
        const dayTotal = dayPoints.reduce((sum, point) => sum + Number(point.amount), 0);
        return total + dayTotal;
      }, 0);
  };

  // Navigate to the previous month
  const prevMonth = () => {
    const newDate = new Date(currentYear, currentMonth - 1, 1);
    setDate(newDate);
  };

  // Navigate to next month - add check for future dates
  const nextMonth = () => {
    const newDate = new Date(currentYear, currentMonth + 1, 1);

    // If disableFuture is true, don't allow navigation beyond the current month
    if (disableFuture) {
      const today = new Date();
      if (newDate.getFullYear() > today.getFullYear() ||
          (newDate.getFullYear() === today.getFullYear() &&
           newDate.getMonth() > today.getMonth())) {
        return;
      }
    }

    setDate(newDate);
  };

  // Get points for a specific day
  const getPointsForDay = (day: Date) => {
    const formattedDate = formatDate(day);
    if (activeModality === "all") {
      return userPoints.filter(point => point.date === formattedDate);
    }
    return userPoints.filter(point =>
      point.date === formattedDate &&
      point.modality === activeModality
    );
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

  // Check if a date is in the future
  const isFutureDate = (date: Date) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return date > today;
  };

  // Handle modality change
  const handleModalityChange = (modality: string) => {
    setActiveModality(modality);
  };

  // Fetch user points data
  useEffect(() => {
    const fetchUserPoints = async () => {
      setLoading(true);
      try {
        const formattedDate = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}`;
        const response = await axios.get(route('user.points', { date: formattedDate }));
        setUserPoints(response.data.points);
        setEventInfo(response.data.event);
      } catch (error) {
        console.error('Error fetching user points:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchUserPoints();
  }, [currentYear, currentMonth]);

  const modalities = ["all", "run", "walk", "other"];

  return (
    <div className="w-full rounded-lg border bg-card text-card-foreground shadow-sm">
      <div className="flex flex-col sm:flex-row gap-5 md:items-center justify-between p-4 pb-0 md:pb-4">
        <div className="space-y-2">
          <div className="flex space-x-4">
            <Button variant="outline" size="icon" onClick={prevMonth}>
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <h2 className="text-3xl font-semibold">{formatMonthYear(date)}</h2>
            <Button variant="outline" size="icon" onClick={nextMonth}>
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>

          <p className="text-base text-muted-foreground">
            Click/touch the ± symbol to add or edit mile entries.
          </p>
        </div>
        {/* Modality Filter Buttons */}
        <div className="flex flex-wrap gap-2 px-4 pb-2">
          {modalities.map((modality) => (
            <Button
              key={modality}
              variant={activeModality === modality ? "default" : "outline"}
              size="sm"
              onClick={() => handleModalityChange(modality)}
              className="capitalize"
            >
              {modality.replace('-', ' ')}
            </Button>
          ))}
        </div>
      </div>

      <div className="p-4">
        {loading ? (
          <div className="grid grid-cols-8 gap-0 border border-border">
            {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Total'].map((day) => (
              <div key={day} className="text-center text-sm font-medium text-muted-foreground p-2 border-b border-r border-border">
                {day}
               </div>
            ))}
            {Array.from({ length: 5 }).map((_, weekIndex) => (
              <React.Fragment key={`skeleton-week-${weekIndex}`}>
                {Array.from({ length: 7 }).map((_, dayIndex) => (
                  <div
                    key={`skeleton-day-${weekIndex}-${dayIndex}`}
                    className="aspect-square p-1 border-r border-b border-border"
                  >
                    <div className="h-full w-full">
                      <div className="flex justify-end p-1">
                        <Skeleton className="h-4 w-4" />
                      </div>
                      <div className="space-y-1 p-1 flex flex-col items-center justify-center">
                        <Skeleton className="h-5 w-16" />
                      </div>
                    </div>
                  </div>
                ))}
                {/* Weekly total skeleton */}
                <div className="aspect-square p-1 bg-gray-50/50 dark:bg-black/50 flex items-center justify-center border-b border-border">
                  <Skeleton className="h-5 w-12" />
                </div>
              </React.Fragment>
            ))}
          </div>
        ) : (
          <div className="grid grid-cols-8 gap-0 border border-border">
            {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Total'].map((day) => (
              <div key={day} className="text-center text-sm font-medium text-muted-foreground p-2 border-b border-r border-border">
                {day}
              </div>
            ))}
            {weeks.map((week, weekIndex) => (
              <React.Fragment key={`week-${weekIndex}`}>
                {week.map((day, dayIndex) => (
                  <div
                    key={`day-${weekIndex}-${dayIndex}`}
                    className={cn(
                      "aspect-square p-1 transition-colors border-r border-b border-border",
                      day ? "cursor-pointer hover:bg-accent" : "",
                      day && isToday(day) ? "bg-accent/50" : "",
                      day && selectedDate && day.getDate() === selectedDate.getDate() &&
                      day.getMonth() === selectedDate.getMonth() ? "bg-accent" : "",
                      disableFuture && day && isFutureDate(day) ? "opacity-50 cursor-not-allowed hover:bg-transparent" : ""
                    )}
                    onClick={() => {
                      if (day && !(disableFuture && isFutureDate(day))) {
                        setSelectedDate(day);
                        setIsModalOpen(true);
                      }
                    }}
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
                              title={point.note || `${point.amount} miles (Total: ${point.cumulative_miles})`}
                            >
                              {Number(point.amount).toFixed(2)}±
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                ))}
                {/* Weekly total column */}
                <div className="aspect-square p-1 bg-gray-50/50 dark:bg-black/50 flex items-center justify-center border-b border-border">
                  <div className="truncate text-center text-sm md:text-xl">
                    {getWeeklyTotal(week).toFixed(2)}
                  </div>
                </div>
              </React.Fragment>
            ))}
          </div>
        )}
      </div>

      {/* Points Detail Modal */}
      <PointsDetailModal
        isOpen={isModalOpen}
        onClose={() => {
          setIsModalOpen(false);
          setSelectedDate(null);
        }}
        date={selectedDate}
        eventId={eventInfo?.id || null}
        activeModality={activeModality}
      />
    </div>
  );
}
