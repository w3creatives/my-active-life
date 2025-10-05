import { Check, ChevronDown } from 'lucide-react';

import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import AppLogo from './app-logo';
import { Smartphone } from 'lucide-react';

export function EventSwitcher() {
  const { auth } = usePage().props as any;
  const [isUpdating, setIsUpdating] = useState<number | null>(null);

  // Find the preferred event from participation list
  const preferredEvent = auth.participations?.find((participation: any) => participation.event.id === auth.user.preferred_event_id)?.event;

  const setPreferredEvent = (eventId: number) => {
    if (eventId === auth.user.preferred_event_id || isUpdating) return;

    setIsUpdating(eventId);
    router.post(
      route('user.set-preferred-event'),
      { event_id: eventId },
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Preferred event updated successfully');
        },
        onError: (errors) => {
          toast.error(errors.error || 'Failed to update preferred event');
        },
        onFinish: () => setIsUpdating(null),
      },
    );
  };

  console.log(auth.participations);

  return (
    <SidebarMenu>
      <SidebarMenuItem>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <SidebarMenuButton size="lg" className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground">
              <AppLogo />
              <div className="flex flex-col gap-0.5 leading-none">
                <span className="text-xs">Run The Edge</span>
                <span className="font-semibold">{preferredEvent?.name || 'Run The Edge'}</span>
              </div>
              <ChevronDown className="ml-auto" />
            </SidebarMenuButton>
          </DropdownMenuTrigger>
          <DropdownMenuContent className="w-(--radix-dropdown-menu-trigger-width)" align="start">
            {auth.participations?.map((participation: any) => (
              <DropdownMenuItem
                key={participation.event.id}
                onClick={() => setPreferredEvent(participation.event.id)}
                disabled={isUpdating === participation.event.id}
              >
                <span className="text-sm flex gap-1 items-center">{participation.event.mobile_event ? <Smartphone /> : ''}{participation.event.name}</span>
                {participation.event.id === auth.user.preferred_event_id && <Check className="ml-auto" />}
                {isUpdating === participation.event.id && <span className="ml-auto text-xs opacity-70">Updating...</span>}
              </DropdownMenuItem>
            ))}
          </DropdownMenuContent>
        </DropdownMenu>
      </SidebarMenuItem>
    </SidebarMenu>
  );
}
