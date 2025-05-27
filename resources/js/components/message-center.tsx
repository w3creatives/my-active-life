import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Mail, MailOpen } from 'lucide-react';
import { HTMLAttributes } from 'react';
import { Link } from '@inertiajs/react';

export default function MessageCenter({ className = '', ...props }: HTMLAttributes<HTMLDivElement>) {

  return (
    <div className={className} {...props}>
        <Link href={route('user.conversations')}>
            <Button variant="ghost" size="icon" className="h-9 w-9 rounded-md">
                <Mail className="h-5 w-5" />
                <span className="sr-only">Toggle theme</span>
            </Button>
        </Link>
      {/*<DropdownMenu>*/}
      {/*  <DropdownMenuTrigger asChild>*/}
      {/*    <Link href={route('user.conversations')}>*/}
      {/*      <Button variant="ghost" size="icon" className="h-9 w-9 rounded-md">*/}
      {/*        <Mail className="h-5 w-5" />*/}
      {/*        <span className="sr-only">Toggle theme</span>*/}
      {/*      </Button>*/}
      {/*    </Link>*/}
      {/*  </DropdownMenuTrigger>*/}
      {/*  <DropdownMenuContent align="end" className="space-y-1 hidden">*/}
      {/*    <DropdownMenuItem className="p-2 flex flex-col gap-1 max-w-xs justify-start bg-gray-100 dark:bg-gray-800">*/}
      {/*      <div className="flex w-full justify-between items-center">*/}
      {/*        <span className="text-base font-semibold w-full text-left">Mail Title</span>*/}
      {/*        <Mail />*/}
      {/*      </div>*/}
      {/*      <span>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Alias aspernatur blanditiis dolore dolores...</span>*/}
      {/*    </DropdownMenuItem>*/}
      {/*    <DropdownMenuItem className="p-2 flex flex-col gap-1 max-w-xs justify-start">*/}
      {/*      <div className="flex w-full justify-between items-center">*/}
      {/*        <span className="text-base font-semibold w-full text-left">Mail Title</span>*/}
      {/*        <MailOpen />*/}
      {/*      </div>*/}
      {/*      <span>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Alias aspernatur blanditiis dolore dolores...</span>*/}
      {/*    </DropdownMenuItem>*/}
      {/*    <DropdownMenuItem className="p-2 flex flex-col gap-1 max-w-xs justify-start">*/}
      {/*      <div className="flex w-full justify-between items-center">*/}
      {/*        <span className="text-base font-semibold w-full text-left">Mail Title</span>*/}
      {/*        <MailOpen />*/}
      {/*      </div>*/}
      {/*      <span>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Alias aspernatur blanditiis dolore dolores...</span>*/}
      {/*    </DropdownMenuItem>*/}
      {/*  </DropdownMenuContent>*/}
      {/*</DropdownMenu>*/}
    </div>
  );
}
