import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/react';
import { format } from 'date-fns';
import { CheckCircle2, Loader2 } from 'lucide-react';
import React, { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface DeviceSyncCardProps {
    name: string;
    imageSrc: string;
    description?: string;
    isConnected: boolean;
    connectRoute?: string;
    disconnectRoute?: string;
    modalTitle?: string;
    modalContent?: React.ReactNode;
    onDisconnect?: () => void;
    sourceSlug?: string;
}

export default function DeviceSyncCard({
    name,
    imageSrc,
    description,
    isConnected,
    connectRoute = '#',
    disconnectRoute = '#',
    modalTitle,
    modalContent,
    onDisconnect,
    sourceSlug,
}: DeviceSyncCardProps) {
    const today = format(new Date(), 'yyyy-MM-dd');
    const minDate = '2025-01-01';

    // Set initial date to today, but ensure it's not before minDate
    const initialDate = new Date(today) >= new Date(minDate) ? today : minDate;
    const [dateString, setDateString] = useState<string>(initialDate);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [isDisconnectDialogOpen, setIsDisconnectDialogOpen] = useState(false);
    const [deleteData, setDeleteData] = useState<'no' | 'yes'>('no');
    const [isConnecting, setIsConnecting] = useState(false);
    const [isDisconnecting, setIsDisconnecting] = useState(false);
    const [isSyncing, setIsSyncing] = useState(false);
    const [connectionStatus, setConnectionStatus] = useState<boolean>(isConnected);

    // Check URL parameters for sync_success flag
    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const syncSuccess = urlParams.get('sync_success');
        const source = urlParams.get('source');
        const syncStartDate = urlParams.get('sync_start_date');

        if (syncSuccess === 'true' && source === sourceSlug && isConnected) {
            // Clean up URL parameters
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);

            // Automatically trigger sync after connection with the date that was used for connection
            if (syncStartDate) {
                syncUserPoints(syncStartDate);
            }
        }
    }, [isConnected, sourceSlug]);

    const syncUserPoints = (startDate = today) => {
        if (!sourceSlug) return;

        setIsSyncing(true);
        const toastId = toast.loading(`Syncing data from ${name}...`);

        // Call the sync points API
        fetch('/api/user/event/sync-points', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                sync_start_date: startDate,
                data_source: sourceSlug,
            }),
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then((data) => {
                toast.success(`Successfully synced data from ${name}`, {
                    id: toastId,
                    description: 'Your miles have been updated.',
                });
            })
            .catch((error) => {
                console.error('Error syncing data:', error);
                toast.error(`Failed to sync data from ${name}`, {
                    id: toastId,
                    description: 'Please try again later.',
                });
            })
            .finally(() => {
                setIsSyncing(false);
            });
    };

    const handleConnect = () => {
        setIsConnecting(true);

        // Show toast notification
        toast.promise(
            // This is a fake promise that resolves after a delay
            new Promise((resolve) => {
                setTimeout(() => {
                    try {
                        // Add parameters to indicate this is a new connection and include the sync start date
                        window.location.href = `${connectRoute}?sync_start_date=${dateString}&source=${sourceSlug}&sync_success=true`;
                        resolve(true);
                    } catch (error) {
                        console.error('Error with date:', error);
                        // Fallback to current date if there's an error
                        window.location.href = `${connectRoute}?sync_start_date=${initialDate}&source=${sourceSlug}&sync_success=true`;
                        resolve(true);
                    }
                }, 500); // Small delay to show the loading state
            }),
            {
                loading: `Connecting to ${name}...`,
                success: `Redirecting to ${name} for authorization...`,
                error: `Failed to connect to ${name}. Please try again.`,
            },
        );
    };

    const handleDisconnect = (e: React.MouseEvent) => {
        e.preventDefault();
        // Open the disconnect confirmation dialog
        setIsDisconnectDialogOpen(true);
    };

    const confirmDisconnect = () => {
        setIsDisconnecting(true);
        setIsDisconnectDialogOpen(false);

        // Show loading toast
        const toastId = toast.loading(`Disconnecting from ${name}...`);

        // Use Inertia router to handle the POST request
        router.post(
            disconnectRoute,
            { delete_data: deleteData },
            {
                preserveState: true,
                onSuccess: () => {
                    // Update toast to success
                    toast.success(`Successfully disconnected from ${name}`, {
                        id: toastId,
                    });

                    // Update local state to reflect disconnection
                    setConnectionStatus(false);

                    // Call the onDisconnect callback if provided
                    if (onDisconnect) {
                        onDisconnect();
                    }
                },
                onError: (errors) => {
                    // Update toast to error
                    toast.error(`Failed to disconnect from ${name}. Please try again.`, {
                        id: toastId,
                    });
                    setIsDisconnecting(false);
                },
                onFinish: () => {
                    // Reset disconnecting state
                    setIsDisconnecting(false);
                },
            },
        );
    };

    // Default modal content if none provided
    const defaultModalContent = (
        <>
            <div className="space-y-4">
                <p>Select the date you would like to synchronize miles from. Please allow 30 minutes for data to sync.</p>
                <p className="text-muted-foreground text-sm">Note: You can only select dates between January 1, 2025 and today.</p>
            </div>
        </>
    );

    return (
        <Card>
            <CardHeader className="gap-4">
                <img src={imageSrc} alt={name} className="h-16 w-fit" />
                <CardTitle className="text-2xl">{name}</CardTitle>
                {description && <CardDescription>{description}</CardDescription>}
            </CardHeader>
            <CardContent>
                {connectionStatus ? (
                    <div className="flex items-center justify-between">
                        <Button
                            variant="outline"
                            className="cursor-pointer border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700"
                            onClick={handleDisconnect}
                            disabled={isDisconnecting || isSyncing}
                        >
                            {isDisconnecting ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Disconnecting...
                                </>
                            ) : (
                                'Disconnect'
                            )}
                        </Button>
                        <div className="flex items-center gap-1 text-sm text-green-600">
                            <CheckCircle2 className="h-4 w-4" />
                            <span>Connected</span>
                        </div>

                        {/* Disconnect Confirmation Dialog */}
                        <Dialog open={isDisconnectDialogOpen} onOpenChange={setIsDisconnectDialogOpen}>
                            <DialogContent className="sm:max-w-md">
                                <DialogHeader>
                                    <DialogTitle className="text-2xl">Disconnect {name}</DialogTitle>
                                </DialogHeader>
                                <DialogDescription className="space-y-6 py-4 text-base">
                                    <p className="text-center">You can choose whether you want to keep previously synced miles, or delete all synced entries.</p>

                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <Button
                                            type="button"
                                            variant={deleteData === 'no' ? 'default' : 'outline'}
                                            className={`h-auto px-4 py-2 text-wrap flex flex-col items-center justify-center gap-1 ${deleteData === 'no' ? 'ring-2 ring-primary' : ''}`}
                                            onClick={() => setDeleteData('no')}
                                        >
                                            <div className="text-lg text-wrap font-semibold">Preserve Miles</div>
                                            <div className="text-sm text-wrap text-center">Keep all your previously synced miles</div>
                                        </Button>
                                        <Button
                                            type="button"
                                            variant={deleteData === 'yes' ? 'default' : 'outline'}
                                            className={`h-auto px-4 py-2 text-wrap flex flex-col items-center justify-center gap-1 ${deleteData === 'yes' ? 'ring-2 ring-primary' : ''}`}
                                            onClick={() => setDeleteData('yes')}
                                        >
                                            <div className="text-lg text-wrap font-semibold">Delete Miles</div>
                                            <div className="text-sm text-wrap text-center">Remove all miles synced from this device</div>
                                        </Button>
                                    </div>
                                </DialogDescription>
                                <DialogFooter className="mt-6 flex flex-col sm:flex-row gap-3">
                                    <Button
                                        variant="outline"
                                        onClick={() => setIsDisconnectDialogOpen(false)}
                                        className="w-full sm:w-auto"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        onClick={confirmDisconnect}
                                        disabled={isDisconnecting}
                                        className="w-full sm:w-auto cursor-pointer"
                                    >
                                        {isDisconnecting ? (
                                            <>
                                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                Disconnecting...
                                            </>
                                        ) : (
                                            'Disconnect'
                                        )}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                ) : (
                    <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                        <DialogTrigger asChild>
                            <Button className="cursor-pointer dark:text-white">Connect</Button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle className="text-2xl">{modalTitle || `Connect ${name}`}</DialogTitle>
                            </DialogHeader>
                            <DialogDescription className="space-y-4 py-4 text-base">
                                <div className="space-y-4">{modalContent || defaultModalContent}</div>
                                <div className="space-y-2">
                                    <Label htmlFor="sync-date">Sync Start Date</Label>
                                    <Input
                                        id="sync-date"
                                        type="date"
                                        value={dateString}
                                        onChange={(e) => setDateString(e.target.value)}
                                        min={minDate}
                                        max={today}
                                        className="w-full"
                                    />
                                </div>
                                <Button
                                    className="mt-4 w-full cursor-pointer"
                                    onClick={() => {
                                        handleConnect();
                                        setIsDialogOpen(false);
                                    }}
                                    disabled={isConnecting}
                                >
                                    {isConnecting ? (
                                        <>
                                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                            Connecting...
                                        </>
                                    ) : (
                                        `Connect ${name}`
                                    )}
                                </Button>
                            </DialogDescription>
                        </DialogContent>
                    </Dialog>
                )}
            </CardContent>
        </Card>
    );
}
