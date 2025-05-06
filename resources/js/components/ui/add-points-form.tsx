import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Calendar as CalendarIcon } from 'lucide-react';
import { format } from 'date-fns';
import { cn } from '@/lib/utils';
import { Calendar } from '@/components/ui/calendar';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import axios from 'axios';
import { toast } from 'sonner';

interface AddPointsFormProps {
  eventId: number;
  onSuccess: () => void;
}

export function AddPointsForm({ eventId, onSuccess }: AddPointsFormProps) {
  const [date, setDate] = useState<Date | undefined>(new Date());
  const [miles, setMiles] = useState<string>('');
  const [note, setNote] = useState<string>('');
  const [loading, setLoading] = useState<boolean>(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!date || !miles) {
      toast.error('Please fill in all required fields');
      return;
    }

    setLoading(true);

    try {
      await axios.post(route('user.add-points'), {
        date: format(date, 'yyyy-MM-dd'),
        miles: parseFloat(miles),
        note: note || null,
        event_id: eventId,
      });

      toast.success('Points added successfully');

      // Reset form
      setMiles('');
      setNote('');

      // Notify parent component
      onSuccess();
    } catch (error) {
      console.error('Error adding points:', error);
      toast.error('Failed to add points. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="space-y-2">
        <Label htmlFor="date">Date</Label>
        <Popover>
          <PopoverTrigger asChild>
            <Button
              variant="outline"
              className={cn(
                "w-full justify-start text-left font-normal",
                !date && "text-muted-foreground"
              )}
            >
              <CalendarIcon className="mr-2 h-4 w-4" />
              {date ? format(date, 'PPP') : <span>Pick a date</span>}
            </Button>
          </PopoverTrigger>
          <PopoverContent className="w-auto p-0">
            <Calendar
              mode="single"
              selected={date}
              onSelect={setDate}
              initialFocus
            />
          </PopoverContent>
        </Popover>
      </div>

      <div className="space-y-2">
        <Label htmlFor="miles">Miles</Label>
        <Input
          id="miles"
          type="number"
          step="0.01"
          min="0"
          value={miles}
          onChange={(e) => setMiles(e.target.value)}
          placeholder="Enter miles"
          required
        />
      </div>

      <div className="space-y-2">
        <Label htmlFor="note">Note (optional)</Label>
        <Textarea
          id="note"
          value={note}
          onChange={(e) => setNote(e.target.value)}
          placeholder="Add a note about this entry"
          rows={3}
        />
      </div>

      <Button type="submit" className="w-full" disabled={loading}>
        {loading ? 'Adding...' : 'Add Points'}
      </Button>
    </form>
  );
}
