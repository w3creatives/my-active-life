import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';
import { AlertCircle, Bell, CheckCircle, Clock, Info, Mail, MailOpen } from 'lucide-react';
import { HTMLAttributes, useState } from 'react';

// Define notification types
type NotificationType = 'mail' | 'achievement' | 'alert' | 'info';

interface Notification {
  id: string;
  title: string;
  message: string;
  type: NotificationType;
  isRead: boolean;
  timestamp: string;
  link?: string;
}

export default function NotificationCenter({ className = '', ...props }: HTMLAttributes<HTMLDivElement>) {
  const [notifications, setNotifications] = useState<Notification[]>([
    {
      id: '1',
      title: 'New Achievement Unlocked!',
      message: "Congratulations! You've completed your first 5K run. Keep up the great work!",
      type: 'achievement',
      isRead: false,
      timestamp: '2 minutes ago',
    },
    {
      id: '2',
      title: 'Team Invitation',
      message: 'John Doe has invited you to join their running team "Speed Demons".',
      type: 'mail',
      isRead: false,
      timestamp: '1 hour ago',
      link: '/teams/invitations',
    },
    {
      id: '3',
      title: 'Weekly Challenge Reminder',
      message: "Don't forget to log your runs this week to stay on track with your goals.",
      type: 'info',
      isRead: true,
      timestamp: '3 hours ago',
    },
    {
      id: '4',
      title: 'Event Registration Confirmed',
      message: 'Your registration for the Spring Marathon 2025 has been confirmed.',
      type: 'mail',
      isRead: true,
      timestamp: '1 day ago',
    },
  ]);

  const unreadCount = notifications.filter((n) => !n.isRead).length;

  const markAsRead = (notificationId: string) => {
    setNotifications((prev) => prev.map((notification) => (notification.id === notificationId ? { ...notification, isRead: true } : notification)));
  };

  const markAllAsRead = () => {
    setNotifications((prev) => prev.map((notification) => ({ ...notification, isRead: true })));
  };

  const getNotificationIcon = (type: NotificationType, isRead: boolean) => {
    if (type === 'mail') {
      return isRead ? <MailOpen className="h-4 w-4" /> : <Mail className="h-4 w-4" />;
    } else if (type === 'achievement') {
      return <CheckCircle className="h-4 w-4 text-green-500" />;
    } else if (type === 'alert') {
      return <AlertCircle className="h-4 w-4 text-red-500" />;
    } else {
      return <Info className="h-4 w-4 text-blue-500" />;
    }
  };

  return (
    <div className={className} {...props}>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" size="icon" className="relative">
            <Bell className="h-5 w-5" />
            {unreadCount > 0 && (
              <Badge variant="destructive" className="absolute -top-2 -right-2 flex size-5 items-center justify-center rounded-full p-0 text-xs">
                {unreadCount > 9 ? '9+' : unreadCount}
              </Badge>
            )}
            <span className="sr-only">Notifications</span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-80">
          <div className="flex items-center justify-between px-4 py-2">
            <h3 className="font-semibold">Notifications</h3>
            {unreadCount > 0 && (
              <Button variant="link" size="sm" className="text-muted-foreground hover:text-foreground h-auto p-0 text-xs" onClick={markAllAsRead}>
                Mark all as read
              </Button>
            )}
          </div>
          <DropdownMenuSeparator />

          <ScrollArea className="h-80">
            {notifications.length === 0 ? (
              <div className="flex flex-col items-center justify-center p-8 text-center">
                <Bell className="text-muted-foreground/50 mb-2 h-8 w-8" />
                <p className="text-muted-foreground text-sm">No notifications yet</p>
              </div>
            ) : (
              <div className="p-1">
                {notifications.map((notification) => (
                  <DropdownMenuItem
                    key={notification.id}
                    className={cn('flex cursor-pointer flex-col items-start gap-2 p-3', !notification.isRead && 'bg-muted/50')}
                    onClick={() => markAsRead(notification.id)}
                  >
                    <div className="flex w-full items-start gap-3">
                      <div className="mt-0.5 flex-shrink-0">{getNotificationIcon(notification.type, notification.isRead)}</div>
                      <div className="min-w-0 flex-1 space-y-1">
                        <div className="flex items-center justify-between">
                          <p className={cn('text-sm leading-none font-medium', notification.isRead ? 'text-muted-foreground' : 'text-foreground')}>
                            {notification.title}
                          </p>
                          {!notification.isRead && <div className="bg-primary h-2 w-2 rounded-full" />}
                        </div>
                        <p className="text-muted-foreground line-clamp-2 text-xs">{notification.message}</p>
                        <div className="text-muted-foreground flex items-center gap-1 text-xs">
                          <Clock className="h-3 w-3" />
                          {notification.timestamp}
                        </div>
                      </div>
                    </div>
                  </DropdownMenuItem>
                ))}
              </div>
            )}
          </ScrollArea>

          {notifications.length > 0 && (
            <>
              <DropdownMenuSeparator />
              <div className="flex p-2">
                <Button variant="link" size="sm" className="text-muted-foreground hover:text-foreground h-auto w-full text-center text-xs">
                  View all notifications
                </Button>
              </div>
            </>
          )}
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
