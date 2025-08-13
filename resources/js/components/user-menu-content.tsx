import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { type User } from '@/types';
import { Link } from '@inertiajs/react';
import { LogOut, Settings, UserPen, Watch } from 'lucide-react';

interface UserMenuContentProps {
  user: User;
}

export function UserMenuContent({ user }: UserMenuContentProps) {
  const cleanup = useMobileNavigation();

  return (
    <>
      <DropdownMenuLabel className="p-0 font-normal">
        <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
          <UserInfo user={user} showEmail={true} />
        </div>
      </DropdownMenuLabel>
      <DropdownMenuSeparator />
      <DropdownMenuGroup className="space-y-1 py-1">
        <DropdownMenuItem asChild>
          <Link className="block w-full cursor-pointer gap-4" href="#" as="button" prefetch onClick={cleanup}>
            <UserPen />
            View My Profile
          </Link>
        </DropdownMenuItem>
        <DropdownMenuItem asChild>
          <Link className="block w-full cursor-pointer gap-4" href={route('profile.edit')} as="button" prefetch onClick={cleanup}>
            <Settings />
            Account Settings
          </Link>
        </DropdownMenuItem>
        <DropdownMenuItem asChild>
          <Link className="block w-full cursor-pointer gap-4" href={route('profile.device-sync.edit')} as="button" prefetch onClick={cleanup}>
            <Watch />
            Device Syncing
          </Link>
        </DropdownMenuItem>
      </DropdownMenuGroup>
      <DropdownMenuSeparator />
      <DropdownMenuItem asChild>
        <Link className="block w-full cursor-pointer gap-4" method="post" href={route('logout')} as="button" onClick={cleanup}>
          <LogOut />
          Log out
        </Link>
      </DropdownMenuItem>
    </>
  );
}
