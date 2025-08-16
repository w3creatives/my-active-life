import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Check, ChevronsUpDown, Inbox, MessageSquare, Send, Trash2, User, X } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Conversations',
    href: route('user.conversations'),
  },
  {
    title: 'New Conversation',
    href: route('user.conversations.new'),
  },
];

// Example user data for the multiselect dropdown
const users = [
  {
    id: 1,
    name: 'John Doe',
    email: 'john.doe@example.com',
    avatar: null,
    role: 'Coach',
  },
  {
    id: 2,
    name: 'Jane Smith',
    email: 'jane.smith@example.com',
    avatar: null,
    role: 'Team Member',
  },
  {
    id: 3,
    name: 'Mike Johnson',
    email: 'mike.johnson@example.com',
    avatar: null,
    role: 'Admin',
  },
  {
    id: 4,
    name: 'Sarah Williams',
    email: 'sarah.williams@example.com',
    avatar: null,
    role: 'Team Member',
  },
  {
    id: 5,
    name: 'Alex Brown',
    email: 'alex.brown@example.com',
    avatar: null,
    role: 'Coach',
  },
  {
    id: 6,
    name: 'Emily Davis',
    email: 'emily.davis@example.com',
    avatar: null,
    role: 'Team Member',
  },
  {
    id: 7,
    name: 'Robert Wilson',
    email: 'robert.wilson@example.com',
    avatar: null,
    role: 'Admin',
  },
];

export default function NewConversation() {
  const [selectedRecipients, setSelectedRecipients] = useState<number[]>([]);
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [activeFolder, setActiveFolder] = useState('inbox');
  const [open, setOpen] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Handle form submission logic here
    console.log({
      recipients: selectedRecipients.map((id) => users.find((user) => user.id === id)),
      subject,
      message,
    });
    // Redirect to conversations page after sending
    window.location.href = route('user.conversations');
  };

  const handleCancel = () => {
    // Redirect back to conversations page
    window.location.href = route('user.conversations');
  };

  const toggleRecipient = (userId: number) => {
    setSelectedRecipients((current) => (current.includes(userId) ? current.filter((id) => id !== userId) : [...current, userId]));
  };

  const removeRecipient = (userId: number, e: React.MouseEvent) => {
    e.stopPropagation();
    setSelectedRecipients((current) => current.filter((id) => id !== userId));
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="New Conversation" />
      <div className="flex h-[calc(100vh-4rem)] overflow-hidden border-r border-l">
        {/* Sidebar */}
        <div className="flex w-64 flex-col border-r">
          <div className="border-b p-4">
            <Button variant="default" className="w-full justify-start gap-2">
              <MessageSquare size={16} />
              <span>New Message</span>
            </Button>
          </div>
          <nav className="space-y-1 p-2">
            <Link href={route('user.conversations')}>
              <Button variant={activeFolder === 'inbox' ? 'secondary' : 'ghost'} className="w-full justify-start gap-2">
                <Inbox size={16} />
                <span>Inbox</span>
              </Button>
            </Link>
            <Button
              variant={activeFolder === 'sent' ? 'secondary' : 'ghost'}
              className="w-full justify-start gap-2"
              onClick={() => setActiveFolder('sent')}
            >
              <Send size={16} />
              <span>Sent</span>
            </Button>
            <Button
              variant={activeFolder === 'trash' ? 'secondary' : 'ghost'}
              className="w-full justify-start gap-2 text-red-500"
              onClick={() => setActiveFolder('trash')}
            >
              <Trash2 size={16} />
              <span>Trash</span>
            </Button>
          </nav>
        </div>

        {/* Main Content */}
        <div className="flex flex-1 flex-col">
          <div className="border-b p-4">
            <h2 className="text-xl font-bold">New Message</h2>
          </div>

          <form onSubmit={handleSubmit} className="flex flex-1 flex-col">
            <div className="border-b p-4">
              <div className="space-y-4">
                <div className="flex items-start">
                  <Label htmlFor="recipients" className="w-20 flex-shrink-0 pt-2">
                    To:
                  </Label>
                  <div className="flex-1">
                    <Popover open={open} onOpenChange={setOpen}>
                      <PopoverTrigger asChild>
                        <Button variant="outline" role="combobox" aria-expanded={open} className="h-auto min-h-10 w-full justify-between py-1">
                          <div className="flex flex-wrap items-center gap-1">
                            {selectedRecipients.length === 0 ? (
                              <span className="text-muted-foreground">Select recipients...</span>
                            ) : (
                              selectedRecipients.map((id) => {
                                const user = users.find((u) => u.id === id);
                                return (
                                  <Badge key={id} variant="secondary" className="flex items-center gap-1 pr-1 pl-1">
                                    <div className="mr-1 flex h-5 w-5 items-center justify-center rounded-full bg-gray-200">
                                      <User size={12} />
                                    </div>
                                    <span>{user?.name}</span>
                                    <Button
                                      variant="ghost"
                                      size="sm"
                                      className="ml-1 h-4 w-4 p-0 hover:bg-transparent"
                                      onClick={(e) => removeRecipient(id, e)}
                                    >
                                      <X size={12} />
                                    </Button>
                                  </Badge>
                                );
                              })
                            )}
                          </div>
                          <ChevronsUpDown size={16} className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                        </Button>
                      </PopoverTrigger>
                      <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0" align="start">
                        <Command>
                          <CommandInput placeholder="Search users..." />
                          <CommandList>
                            <CommandEmpty>No users found.</CommandEmpty>
                            <CommandGroup>
                              {users.map((user) => (
                                <CommandItem
                                  key={user.id}
                                  value={user.name}
                                  onSelect={() => {
                                    toggleRecipient(user.id);
                                    setOpen(true); // Keep the dropdown open after selection
                                  }}
                                >
                                  <div className="flex w-full items-center">
                                    <div className="mr-2 flex h-7 w-7 items-center justify-center rounded-full bg-gray-200">
                                      <User size={14} />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                      <p className="truncate font-medium">{user.name}</p>
                                      <p className="text-muted-foreground truncate text-xs">{user.email}</p>
                                    </div>
                                    <div className="ml-2 flex shrink-0 items-center">
                                      <Badge variant="outline" className="mr-2">
                                        {user.role}
                                      </Badge>
                                      <Check className={cn('h-4 w-4', selectedRecipients.includes(user.id) ? 'opacity-100' : 'opacity-0')} />
                                    </div>
                                  </div>
                                </CommandItem>
                              ))}
                            </CommandGroup>
                          </CommandList>
                        </Command>
                      </PopoverContent>
                    </Popover>
                  </div>
                </div>

                <div className="flex items-center">
                  <Label htmlFor="subject" className="w-20 flex-shrink-0">
                    Subject:
                  </Label>
                  <Input
                    id="subject"
                    placeholder="Enter subject"
                    value={subject}
                    onChange={(e) => setSubject(e.target.value)}
                    required
                    className="flex-1"
                  />
                </div>
              </div>
            </div>

            <div className="flex-1 overflow-auto p-4">
              <Textarea
                id="message"
                placeholder="Type your message here..."
                value={message}
                onChange={(e) => setMessage(e.target.value)}
                className="min-h-[calc(100%-1rem)] w-full resize-none"
                required
              />
            </div>

            <div className="border-t p-4">
              <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={handleCancel} className="gap-2">
                  <X size={16} />
                  <span>Cancel</span>
                </Button>
                <Button type="submit" className="gap-2">
                  <Send size={16} />
                  <span>Send</span>
                </Button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}
