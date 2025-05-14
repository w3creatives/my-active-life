import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { format } from 'date-fns';
import { useEffect } from 'react';

interface PointsDetailModalProps {
    isOpen: boolean;
    onClose: () => void;
    date: Date | null;
    eventId: number | null;
}

export function PointsDetailModal({ isOpen, onClose, date, eventId }: PointsDetailModalProps) {
    // Fetch point details when the modal opens
    useEffect(() => {
    }, [isOpen, date, eventId]);

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md max-h-[90vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle className="text-xl">Points for {date ? format(date, 'MMMM d, yyyy') : ''}</DialogTitle>
                </DialogHeader>

                <ScrollArea className="flex-1 overflow-y-auto">
                    <div className="space-y-6 py-4 pr-2">
                        {/* Fitbit Section */}
                        <div className="space-y-4">
                            <h2 className="text-xl">Activities Synced From Fitbit</h2>
                            <hr />
                            <div key="fitbit-1" className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label className="text-base font-medium">Other Miles</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Input type="number" step="0.01" defaultValue="5.05" className="w-full" />
                                    <span className="text-sm">miles</span>
                                </div>
                            </div>
                            <div key="fitbit-2" className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label className="text-base font-medium">Running Miles</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Input type="number" step="0.01" defaultValue="5.05" className="w-full" />
                                    <span className="text-sm">miles</span>
                                </div>
                            </div>
                            <div key="fitbit-3" className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label className="text-base font-medium">Walking Miles</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Input type="number" step="0.01" defaultValue="5.05" className="w-full" />
                                    <span className="text-sm">miles</span>
                                </div>
                            </div>
                        </div>

                        {/* Strava Section */}
                        <div className="space-y-4">
                            <h2 className="text-xl">Activities Synced From Strava</h2>
                            <hr />
                            <div key="strava-1" className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label className="text-base font-medium">Other Miles</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Input type="number" step="0.01" defaultValue="3.25" className="w-full" />
                                    <span className="text-sm">miles</span>
                                </div>
                            </div>
                            <div key="strava-2" className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label className="text-base font-medium">Running Miles</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Input type="number" step="0.01" defaultValue="7.15" className="w-full" />
                                    <span className="text-sm">miles</span>
                                </div>
                            </div>
                            <div key="strava-3" className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label className="text-base font-medium">Walking Miles</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Input type="number" step="0.01" defaultValue="2.30" className="w-full" />
                                    <span className="text-sm">miles</span>
                                </div>
                            </div>
                        </div>

                        {/* Garmin Section */}
                        <div className="space-y-4">
                            <h2 className="text-xl">Activities Synced From Garmin</h2>
                            <hr />
                            <div key="garmin-1" className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label className="text-base font-medium">Other Miles</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Input type="number" step="0.01" defaultValue="1.75" className="w-full" />
                                    <span className="text-sm">miles</span>
                                </div>
                            </div>
                            <div key="garmin-2" className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label className="text-base font-medium">Running Miles</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Input type="number" step="0.01" defaultValue="4.50" className="w-full" />
                                    <span className="text-sm">miles</span>
                                </div>
                            </div>
                            <div key="garmin-3" className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label className="text-base font-medium">Walking Miles</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Input type="number" step="0.01" defaultValue="3.20" className="w-full" />
                                    <span className="text-sm">miles</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </ScrollArea>

                <DialogFooter className="gap-2 mt-4 pt-2 border-t">
                    <Button variant="outline" onClick={onClose}>Cancel</Button>
                    <Button onClick={() => {
                        console.log('Changes saved');
                        onClose();
                    }}>Save Changes</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
