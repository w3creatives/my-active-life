import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import {
  Inbox,
  MessageSquare,
  Send,
  Trash2,
  X,
  User
} from 'lucide-react';

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

export default function NewConversation() {
  const [recipient, setRecipient] = useState('');
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [activeFolder, setActiveFolder] = useState('inbox');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Handle form submission logic here
    console.log({ recipient, subject, message });
    // Redirect to conversations page after sending
    window.location.href = route('user.conversations');
  };

  const handleCancel = () => {
    // Redirect back to conversations page
    window.location.href = route('user.conversations');
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="New Conversation" />
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

        {/* Main Content */}
        <div className="flex-1 flex flex-col">
          <div className="p-4 border-b">
            <h2 className="text-xl font-bold">New Message</h2>
          </div>

          <form onSubmit={handleSubmit} className="flex-1 flex flex-col">
            <div className="p-4 border-b">
              <div className="space-y-4">
                <div className="flex items-center">
                  <Label htmlFor="recipient" className="w-20 flex-shrink-0">To:</Label>
                  <Input
                    id="recipient"
                    placeholder="Enter recipient name or email"
                    value={recipient}
                    onChange={(e) => setRecipient(e.target.value)}
                    required
                    className="flex-1"
                  />
                </div>

                <div className="flex items-center">
                  <Label htmlFor="subject" className="w-20 flex-shrink-0">Subject:</Label>
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

            <div className="flex-1 p-4 overflow-auto">
              <Textarea
                id="message"
                placeholder="Type your message here..."
                value={message}
                onChange={(e) => setMessage(e.target.value)}
                className="min-h-[calc(100%-1rem)] w-full resize-none"
                required
              />
            </div>

            <div className="p-4 border-t">
              <div className="flex gap-2 justify-end">
                <Button
                  type="button"
                  variant="outline"
                  onClick={handleCancel}
                  className="gap-2"
                >
                  <X size={16} />
                  <span>Cancel</span>
                </Button>
                <Button
                  type="submit"
                  className="gap-2"
                >
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
