import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Inbox,
  MessageSquare,
  MoreVertical,
  Search,
  Send,
  Star,
  Trash2,
  User
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Conversations',
    href: route('user.conversations'),
  },
];

// Mock data for conversations
const conversations = [
  {
    id: 1,
    sender: 'John Doe',
    subject: 'Training Schedule Update',
    message: 'I wanted to discuss the upcoming training schedule for the team...',
    date: '2023-10-15T10:30:00',
    read: true,
    starred: false,
  },
  {
    id: 2,
    sender: 'Jane Smith',
    subject: 'Event Registration Confirmation',
    message: 'Thank you for registering for our upcoming marathon event...',
    date: '2023-10-14T14:45:00',
    read: false,
    starred: true,
  },
  {
    id: 3,
    sender: 'Team Support',
    subject: 'Your Recent Achievement',
    message: 'Congratulations on reaching your milestone of 500 miles!',
    date: '2023-10-13T09:15:00',
    read: true,
    starred: false,
  },
  {
    id: 4,
    sender: 'Mike Johnson',
    subject: 'Question about Team Membership',
    message: 'I was wondering if there are still spots available on your team...',
    date: '2023-10-12T16:20:00',
    read: false,
    starred: false,
  },
  {
    id: 5,
    sender: 'Event Coordinator',
    subject: 'Important Event Update',
    message: 'Please note that the starting time for the event has been changed...',
    date: '2023-10-11T11:05:00',
    read: true,
    starred: true,
  },
];

export default function Conversations() {
  const [selectedConversation, setSelectedConversation] = useState<number | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [activeFolder, setActiveFolder] = useState('inbox');

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  };

  const selectedMessage = conversations.find(conv => conv.id === selectedConversation);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Conversations" />
      <div className="flex h-[calc(100vh-4rem)] overflow-hidden">
        {/* Sidebar */}
        <div className="w-64 border-r flex flex-col">
          <div className="p-4 border-b">
            <Button variant="default" className="w-full justify-start gap-2">
              <MessageSquare size={16} />
              <span>New Message</span>
            </Button>
          </div>
          <nav className="p-2 space-y-1">
            <Button
              variant={activeFolder === 'inbox' ? 'secondary' : 'ghost'}
              className="w-full justify-start gap-2"
              onClick={() => setActiveFolder('inbox')}
            >
              <Inbox size={16} />
              <span>Inbox</span>
            </Button>
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

        {/* Message List */}
        <div className="w-80 border-r flex flex-col">
          <div className="p-2 border-b">
            <div className="relative">
              <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search messages..."
                className="pl-8"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
          </div>
          <div className="overflow-auto flex-1">
            {conversations.map((conversation) => (
              <div
                key={conversation.id}
                className={`p-3 border-b cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 ${
                  selectedConversation === conversation.id ? 'bg-gray-100 dark:bg-gray-800' : ''
                } ${!conversation.read ? 'font-semibold' : ''}`}
                onClick={() => setSelectedConversation(conversation.id)}
              >
                <div className="flex justify-between items-start mb-1">
                  <span className="truncate">{conversation.sender}</span>
                  <span className="text-xs text-gray-500">{formatDate(conversation.date)}</span>
                </div>
                <div className="font-medium truncate">{conversation.subject}</div>
                <div className="text-sm text-gray-500 truncate">{conversation.message}</div>
                <div className="flex gap-1 mt-1">
                  {conversation.starred && <Star size={14} className="text-yellow-500" />}
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Message Content */}
        <div className="flex-1 flex flex-col">
          {selectedMessage ? (
            <>
              <div className="p-4 border-b">
                <div className="flex justify-between items-center mb-4">
                  <h2 className="text-xl font-bold">{selectedMessage.subject}</h2>
                  <div className="flex gap-2">
                    <Button variant="ghost" size="icon">
                      <Trash2 size={18} />
                    </Button>
                    <Button variant="ghost" size="icon">
                      <MoreVertical size={18} />
                    </Button>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                    <User size={20} />
                  </div>
                  <div className="flex-1">
                    <div className="flex justify-between">
                      <span className="font-semibold">{selectedMessage.sender}</span>
                      <span className="text-sm text-gray-500">
                        {new Date(selectedMessage.date).toLocaleString()}
                      </span>
                    </div>
                    <span className="text-sm text-gray-500">To: Me</span>
                  </div>
                </div>
              </div>
              <div className="p-4 overflow-auto flex-1">
                <p className="whitespace-pre-line">{selectedMessage.message}</p>
                <p className="mt-4">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam auctor, nisl eget ultricies tincidunt, nisl nisl aliquam nisl, eget ultricies nisl nisl eget nisl.</p>
                <p className="mt-4">Best regards,<br />{selectedMessage.sender}</p>
              </div>
              <div className="p-4 border-t">
                <div className="flex gap-2">
                  <Button variant="default">
                    <span>Reply</span>
                  </Button>
                  <Button variant="outline">
                    <span>Forward</span>
                  </Button>
                </div>
              </div>
            </>
          ) : (
            <div className="flex flex-col items-center justify-center h-full text-gray-500">
              <Inbox size={48} className="mb-4" />
              <h3 className="text-xl font-medium mb-2">Select a conversation</h3>
              <p>Choose a conversation from the list to view its contents</p>
            </div>
          )}
        </div>
      </div>
    </AppLayout>
  );
}
