import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Skeleton } from '@/components/ui/skeleton';
import axios from 'axios';
import { format } from 'date-fns';
import { useEffect, useState } from 'react';

interface PointsDetailModalProps {
  isOpen: boolean;
  onClose: () => void;
  date: Date | null;
  eventId: number | null;
  activeModality?: string;
}

interface PointItem {
  id: number;
  amount: string;
  date: string;
  user_id: number;
  data_source_id: number;
  event_id: number;
  created_at: string;
  updated_at: string;
  modality: string;
  transaction_id: string | null;
  note: string | null;
}

interface PointsResponse {
  points: {
    [key: string]: PointItem[];
  };
}

// Map data source IDs to their names
const DATA_SOURCES = {
  '1': 'manual',
  '2': 'fitbit',
  '3': 'garmin',
  '4': 'strava',
  '5': 'apple',
};

export function PointsDetailModal({ isOpen, onClose, date, eventId, activeModality }: PointsDetailModalProps) {
  const [pointsData, setPointsData] = useState<PointsResponse | null>(null);
  const [loading, setLoading] = useState(false);

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
        setPointsData(response.data);
      })
      .catch((error) => {
        console.error('Error fetching point details:', error);
      })
      .finally(() => {
        setLoading(false);
      });
  }, [isOpen, date, eventId, activeModality]);

  const handleSave = () => {
    // TODO: Implement save functionality
    console.log('Changes saved');
    onClose();
  };

  // Helper function to get point amount by data source and modality
  const getPointAmount = (dataSourceId: string, modality: string): number => {
    if (!pointsData?.points[dataSourceId]) return 0;

    const point = pointsData.points[dataSourceId].find((p) => p.modality === modality);
    return point ? parseFloat(point.amount) : 0;
  };

  // Helper function to check if a data source has any points
  const hasDataSourcePoints = (dataSourceId: string): boolean => {
    return pointsData?.points[dataSourceId]?.length > 0;
  };

  // Helper function to check if a modality has a non-zero value for a data source
  const hasModalityValue = (dataSourceId: string, modality: string): boolean => {
    return getPointAmount(dataSourceId, modality) > 0;
  };

  // Format number to 2 decimal places
  const formatValue = (value: number): string => {
    return value.toFixed(2);
  };

  // Get all modalities that have values for a data source
  const getActiveModalities = (dataSourceId: string): string[] => {
    const modalities = ['run', 'walk', 'bike', 'swim', 'other'];
    return modalities.filter((modality) => hasModalityValue(dataSourceId, modality));
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
              {/* Garmin Section */}
              {hasDataSourcePoints('3') && getActiveModalities('3').length > 0 && (
                <div className="space-y-4">
                  <h2 className="text-xl">Activities Synced From Garmin</h2>
                  <hr />
                  <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-5">
                    {getActiveModalities('3').map((modality) => (
                      <div key={`garmin-${modality}`} className="space-y-1">
                        <div className="flex items-center justify-between">
                          <Label className="text-sm font-medium">{modality.charAt(0).toUpperCase() + modality.slice(1)}</Label>
                        </div>
                        <div className="flex items-center">
                          <Input type="number" step="0.01" defaultValue={formatValue(getPointAmount('3', modality))} className="w-full" />
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Fitbit Section */}
              {hasDataSourcePoints('2') && getActiveModalities('2').length > 0 && (
                <div className="space-y-4">
                  <h2 className="text-xl">Activities Synced From Fitbit</h2>
                  <hr />
                  <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-5">
                    {getActiveModalities('2').map((modality) => (
                      <div key={`fitbit-${modality}`} className="space-y-1">
                        <div className="flex items-center justify-between">
                          <Label className="text-sm font-medium">{modality.charAt(0).toUpperCase() + modality.slice(1)}</Label>
                        </div>
                        <div className="flex items-center">
                          <Input type="number" step="0.01" defaultValue={formatValue(getPointAmount('2', modality))} className="w-full" />
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Strava Section */}
              {hasDataSourcePoints('4') && getActiveModalities('4').length > 0 && (
                <div className="space-y-4">
                  <h2 className="text-xl">Activities Synced From Strava</h2>
                  <hr />
                  <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-5">
                    {getActiveModalities('4').map((modality) => (
                      <div key={`strava-${modality}`} className="space-y-1">
                        <div className="flex items-center justify-between">
                          <Label className="text-sm font-medium">{modality.charAt(0).toUpperCase() + modality.slice(1)}</Label>
                        </div>
                        <div className="flex items-center">
                          <Input type="number" step="0.01" defaultValue={formatValue(getPointAmount('4', modality))} className="w-full" />
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Apple Section */}
              {hasDataSourcePoints('5') && getActiveModalities('5').length > 0 && (
                <div className="space-y-4">
                  <h2 className="text-xl">Activities Synced From Apple</h2>
                  <hr />
                  <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-5">
                    {getActiveModalities('5').map((modality) => (
                      <div key={`apple-${modality}`} className="space-y-1">
                        <div className="flex items-center justify-between">
                          <Label className="text-sm font-medium">{modality.charAt(0).toUpperCase() + modality.slice(1)}</Label>
                        </div>
                        <div className="flex items-center">
                          <Input type="number" step="0.01" defaultValue={formatValue(getPointAmount('5', modality))} className="w-full" />
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Manual Section */}
              {hasDataSourcePoints('1') && getActiveModalities('1').length > 0 && (
                <div className="space-y-4">
                  <h2 className="text-xl">Manually Entered Distances</h2>
                  <hr />
                  <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-5">
                    {getActiveModalities('1').map((modality) => (
                      <div key={`manual-${modality}`} className="space-y-1">
                        <div className="flex items-center justify-between">
                          <Label className="text-sm font-medium">{modality.charAt(0).toUpperCase() + modality.slice(1)}</Label>
                        </div>
                        <div className="flex items-center">
                          <Input type="number" step="0.01" defaultValue={formatValue(getPointAmount('1', modality))} className="w-full" />
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Show message if no data */}
              {pointsData && Object.keys(pointsData.points).length === 0 && (
                <div className="py-8 text-center">
                  <p className="text-gray-500">No activity data found for this date.</p>
                </div>
              )}
            </div>
          )}
        </ScrollArea>

        <DialogFooter className="mt-4 gap-2 border-t pt-2">
          <Button variant="outline" onClick={onClose}>
            Cancel
          </Button>
          <Button onClick={handleSave} disabled={loading}>
            Save Changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
