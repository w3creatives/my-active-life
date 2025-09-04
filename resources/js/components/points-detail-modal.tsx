import DatasourcePoint from '@/components/datasource-point';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Skeleton } from '@/components/ui/skeleton';
import axios from 'axios';
import { format } from 'date-fns';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface PointsDetailModalProps {
  isOpen: boolean;
  onClose: () => void;
  date: Date | null;
  eventId: number | null;
  activeModality?: string;
}

interface PointItem {
  points: string;
  data_source_id: number;
  modality: string;
}

interface PointsResponse {
  items: {
    [key: string]: PointItem[];
  };
}

export function PointsDetailModal({ isOpen, onClose, date, eventId, activeModality, refreshCalendar }: PointsDetailModalProps) {
  const [pointsData, setPointsData] = useState([]);
  const [loading, setLoading] = useState(false);
  const [processing, setProcessing] = useState(false);
  const [pointFormData, setPointFormData] = useState({});

  // Fetch point details when the modal opens
  useEffect(() => {
    if (!isOpen || !date || !eventId) return;

    setLoading(true);
    const formattedDate = format(date, 'yyyy-MM-dd');

    axios
      .get(
        route('user.daily.points', {
          date: formattedDate,
          event_id: eventId,
          modality: activeModality || 'all',
        }),
      )
      .then((response) => {

        setPointsData(response.data.items);
      })
      .catch((error) => {
        console.error('Error fetching point details:', error);
      })
      .finally(() => {
        setLoading(false);
      });
  }, [isOpen, date, eventId, activeModality]);

  const handlePointChange = (value, item) => {

      let data = pointFormData;
      data[item.modality] = value > 0?value : item.points;
     setPointFormData(data);
    }

  const handleSave = () => {

      if(Object.keys(pointFormData).length === 0) {
          toast.error('No manual points to save');
          setPointFormData({});
          onClose();
          return;
      }

      setProcessing(true);
      axios.post(route('user.add.manual.points'), {points:pointFormData, date:format(date, 'yyyy-mm-dd'), eventId})
          .then((response) => {
              toast.success(response.data.message);
              setPointFormData({});
              setProcessing(true);
              refreshCalendar();
              onClose();
          })
          .catch((error) => {
              toast.error(error.response.data.message);
              setProcessing(true);
          });
  };
  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="flex max-h-[95vh] flex-col sm:max-w-3xl" aria-describedby="points-detail-description">
        <DialogHeader>
          <DialogTitle className="text-xl">Points for {date ? format(date, 'MMMM d, yyyy') : ''}</DialogTitle>
          <DialogDescription id="points-detail-description">*All values are in miles.</DialogDescription>
        </DialogHeader>

        <ScrollArea className="flex-1 overflow-y-auto">
          {loading ? (
            <div className="space-y-10 py-4 pr-2">
              {/* Skeleton for first data source */}
              <div className="space-y-4">
                <Skeleton className="h-7 w-64" />
                <hr />
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-5">
                  {[1, 2, 3, 4].map((i) => (
                    <div key={`skeleton-${i}`} className="space-y-1">
                      <div className="flex items-center justify-between">
                        <Skeleton className="h-4 w-20" />
                      </div>
                      <div className="flex items-center">
                        <Skeleton className="h-9 w-full" />
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          ) : (
            <div className="space-y-10 py-4 pr-2">
              {pointsData?.garmin && <DatasourcePoint type="garmin" title="Activities Synced From Garmin" items={pointsData?.garmin} handlePointChange={handlePointChange}/>}
              {pointsData?.strava && <DatasourcePoint type="strava" title="Activities Synced From Strava" items={pointsData?.strava} handlePointChange={handlePointChange}/>}
              {pointsData?.fitbit && <DatasourcePoint type="fitbit" title="Activities Synced From Fitbit" items={pointsData?.fitbit} handlePointChange={handlePointChange}/>}
              {pointsData?.apple && <DatasourcePoint type="apple" title="Activities Synced From Apple" items={pointsData?.apple} handlePointChange={handlePointChange}/>}
              {pointsData?.manual && <DatasourcePoint type="manual" title="Manually Entered Distances" items={pointsData.manual} handlePointChange={handlePointChange}/>}
            </div>
          )}
        </ScrollArea>

        <DialogFooter className="mt-4 gap-2 border-t pt-2">
          <Button variant="outline" onClick={() => {
              onClose();
              setPointFormData({});
          }}>
            Cancel
          </Button>
          <Button onClick={handleSave} disabled={loading || processing}>
            Save Changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
