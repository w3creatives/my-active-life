import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { format } from 'date-fns';
import { useEffect } from 'react';

interface PointsDetailModalProps {
    isOpen: boolean,
    onClose: () => void,
    date: Date | null,
    eventId: number | null,
    activeModality?: string
}

export function PointsDetailModal({ isOpen, onClose, date, eventId, activeModality }: PointsDetailModalProps) {
    // Fetch point details when the modal opens
    useEffect(() => {
    }, [isOpen, date, eventId]);

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-3xl max-h-[95vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle className="text-xl">Points for {date ? format(date, 'MMMM d, yyyy') : ''}</DialogTitle>
                    <p className="text-sm text-muted-foreground">*All values are in miles.</p>
                </DialogHeader>

                <ScrollArea className="flex-1 overflow-y-auto">
                    <div className="space-y-10 py-4 pr-2">
                        <div className="space-y-4">
                            <h2 className="text-xl">Activities Synced From Fitbit</h2>
                            <hr />
                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                                <div key="fitbit-1" className="space-y-1">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm font-medium">Other</Label>
                                    </div>
                                    <div className="flex items-center">
                                        <Input type="number" step="0.01" defaultValue="5.05" className="w-full" />
                                    </div>
                                </div>
                                <div key="fitbit-2" className="space-y-1">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm font-medium">Running</Label>
                                    </div>
                                    <div className="flex items-center">
                                        <Input type="number" step="0.01" defaultValue="5.05" className="w-full" />
                                    </div>
                                </div>
                                <div key="fitbit-3" className="space-y-1">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm font-medium">Walking</Label>
                                    </div>
                                    <div className="flex items-center">
                                        <Input type="number" step="0.01" defaultValue="5.05" className="w-full" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-4">
                            <h2 className="text-xl">Manually Entered Distances</h2>
                            <hr />
                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                                <div key="garmin-2" className="space-y-1">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm font-medium">Running</Label>
                                    </div>
                                    <div className="flex items-center">
                                        <Input type="number" step="0.01" defaultValue="" className="w-full" />
                                    </div>
                                </div>
                                <div key="garmin-3" className="space-y-1">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm font-medium">Walking</Label>
                                    </div>
                                    <div className="flex items-center">
                                        <Input type="number" step="0.01" defaultValue=" " className="w-full" />
                                    </div>
                                </div>
                                <div key="garmin-3" className="space-y-1">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm font-medium">Biking</Label>
                                    </div>
                                    <div className="flex items-center">
                                        <Input type="number" step="0.01" defaultValue=" " className="w-full" />
                                    </div>
                                </div>
                                <div key="garmin-3" className="space-y-1">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm font-medium">Swimming</Label>
                                    </div>
                                    <div className="flex items-center">
                                        <Input type="number" step="0.01" defaultValue=" " className="w-full" />
                                    </div>
                                </div>
                                <div key="garmin-1" className="space-y-1">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm font-medium">Other</Label>
                                    </div>
                                    <div className="flex items-center">
                                        <Input type="number" step="0.01" defaultValue="" className="w-full" />
                                    </div>
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
