import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
  Inbox,
  MessageSquare,
  MoreVertical,
  Search,
  Send,
  SendIcon,
  Trash2,
  User
} from 'lucide-react';
import { useState, useEffect } from 'react';
import axios from 'axios';
import Sidebar from './mailbox/components/sidebar';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Conversations',
    href: route('user.conversations'),
  },
];

// Define types for our data
interface Notification {
  id: number;
  body: string;
  sender_id: number;
  sender_display_name: string | null;
  created_at: string;
  attachment: string | null;
  type: string;
  receipts: any[];
}

interface Conversation {
  id: number;
  subject: string;
  last_message: {
    body: string;
    created_at: string;
    sender_id: number;
    sender_display_name: string | null;
  } | null;
  created_at: string;
  updated_at: string;
  notifications: Notification[];
}

interface Message {
  id: number;
  body: string;
  sender: {
    id: number;
    name: string;
  };
  created_at: string;
}

interface ConversationDetail {
  id: number;
  subject: string;
  participants: Array<{
    id: number;
    name: string;
  }>;
  messages: Message[];
}

export default function Conversations() {
  const { auth, conversations: conversationsData } = usePage().props;
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [selectedConversations, setSelectedConversations] = useState<number[]>([]);
  const [selectedConversation, setSelectedConversation] = useState<Conversation | null>(null);
  const [conversationDetail, setConversationDetail] = useState<ConversationDetail | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [activeFolder, setActiveFolder] = useState('inbox');
  const [replyMessage, setReplyMessage] = useState('');
  const [loading, setLoading] = useState(true);
  const [loadingDetail, setLoadingDetail] = useState(false);

  // Parse conversations from props
  useEffect(() => {
    try {
      // Check if conversationsData is a string that needs parsing
      if (typeof conversationsData === 'string') {
        setConversations(JSON.parse(conversationsData));
      } else {
        // If it's already an object, use it directly
        setConversations(conversationsData as Conversation[] || []);
      }
      setLoading(false);
    } catch (error) {
      console.error('Error parsing conversations data:', error);
      setLoading(false);
    }
  }, [conversationsData]);

  // Set conversation detail when a conversation is selected
  useEffect(() => {
    if (selectedConversation) {
      setLoadingDetail(true);
      // Use the notifications from the selected conversation as messages
      const detail: ConversationDetail = {
        id: selectedConversation.id,
        subject: selectedConversation.subject,
        participants: [], // We don't have this data in the current structure
        messages: selectedConversation.notifications.map(notification => ({
          id: notification.id,
          body: notification.body,
          sender: {
            id: notification.sender_id,
            name: notification.sender_display_name || 'Unknown User'
          },
          created_at: notification.created_at
        }))
      };
      setConversationDetail(detail);
      setLoadingDetail(false);
    } else {
      setConversationDetail(null);
    }
  }, [selectedConversation]);

  // Since notifications are now sorted by created_at ASC in the backend,
  // the last message is the last item in the notifications array
  const getLastMessage = (conversation: Conversation) => {
    if (!conversation.notifications || conversation.notifications.length === 0) {
      return null;
    }
    // Get the last notification (most recent) from the array
    const lastNotification = conversation.notifications[conversation.notifications.length - 1];
    return {
      body: lastNotification.body,
      created_at: lastNotification.created_at,
      sender_id: lastNotification.sender_id
    };
  };

  // Filter conversations based on search query
  const filteredConversations = conversations.filter(
    (conversation) =>
      conversation.subject.toLowerCase().includes(searchQuery.toLowerCase()) ||
      conversation.last_message?.body?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      conversation.notifications.some(msg => msg.body.toLowerCase().includes(searchQuery.toLowerCase()))
  );

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  };

  const formatTime = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
  };

  const handleSelectConversation = (id: number, checked: boolean) => {
    setSelectedConversations((prev) => (checked ? [...prev, id] : prev.filter((convId) => convId !== id)));
  };

  const handleSelectAll = (checked: boolean) => {
    setSelectedConversations(checked ? filteredConversations.map((conv) => conv.id) : []);
  };

  const handleSendReply = async () => {
    if (replyMessage.trim() === '' || !selectedConversation) return;

    try {
      await axios.post(route('api.conversations.reply', selectedConversation.id), {
        body: replyMessage
      });

      // Refresh the conversations list
      const response = await axios.get(route('user.conversations'));
      const conversationsData = JSON.parse(response.data.props.conversations);
      setConversations(conversationsData);

      // Update the selected conversation
      const updatedConversation = conversationsData.find(
        (conv: Conversation) => conv.id === selectedConversation.id
      );
      setSelectedConversation(updatedConversation || null);

      // Clear the input field
      setReplyMessage('');
    } catch (error) {
      console.error('Error sending reply:', error);
    }
  };

  const handleMoveToTrash = async (ids: number[]) => {
    try {
      await Promise.all(
        ids.map(id => axios.post(route('api.conversations.trash', id)))
      );

      // Refresh the conversations list
      const response = await axios.get(route('user.conversations'));
      const conversationsData = JSON.parse(response.data.props.conversations);
      setConversations(conversationsData);

      // Clear selection
      setSelectedConversations([]);

      // If the currently viewed conversation was moved to trash, clear it
      if (selectedConversation && ids.includes(selectedConversation.id)) {
        setSelectedConversation(null);
      }
    } catch (error) {
      console.error('Error moving conversations to trash:', error);
    }
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Conversations" />
      <div className="flex h-[calc(100vh-4rem)] max-h-[calc(100vh-4rem)] border-r">
        <div className="flex flex-col border-r w-64">
          <div className="p-3 border-b">
            <Link href={route('user.conversations.new')}>
              <Button variant="default" className="justify-start gap-2 w-full">
                <MessageSquare size={16} />
                <span>New Message</span>
              </Button>
            </Link>
          </div>
          <nav className="space-y-1 p-2">
            <Button
              variant={activeFolder === 'inbox' ? 'secondary' : 'ghost'}
              className="justify-start gap-2 w-full"
              onClick={() => setActiveFolder('inbox')}
            >
              <Inbox size={16} />
              <span>Inbox</span>
            </Button>
            <Button
              variant={activeFolder === 'sent' ? 'secondary' : 'ghost'}
              className="justify-start gap-2 w-full"
              onClick={() => setActiveFolder('sent')}
            >
              <Send size={16} />
              <span>Sent</span>
            </Button>
            <Button
              variant={activeFolder === 'trash' ? 'secondary' : 'ghost'}
              className="justify-start gap-2 w-full text-red-500"
              onClick={() => setActiveFolder('trash')}
            >
              <Trash2 size={16} />
              <span>Trash</span>
            </Button>
          </nav>
        </div>

        {/* Message List */}
        <div className="flex flex-col border-r w-80">
          {/* Add select all checkboxes */}
          <div className="flex items-center p-3 border-b h-10">
            <div className="flex items-center">
              <Checkbox
                id="select-all"
                checked={selectedConversations.length === filteredConversations.length && filteredConversations.length > 0}
                onCheckedChange={(checked) => handleSelectAll(checked as boolean)}
                className="mr-2 border-primary"
                disabled={filteredConversations.length === 0}
              />
              <label htmlFor="select-all" className="text-gray-500 text-xs cursor-pointer">
                Select All
              </label>
            </div>
            <div className="ml-auto h-6">
              {selectedConversations.length > 0 ? (
                <Button
                  variant="ghost"
                  size="sm"
                  className="px-2 h-6 text-red-500 hover:bg-background hover:text-red-600 text-xs"
                  onClick={() => handleMoveToTrash(selectedConversations)}
                >
                  <Trash2 size={14} />
                  <span>Delete</span>
                </Button>
              ) : (
                <div className="h-6"></div> // Empty placeholder to maintain height
              )}
            </div>
          </div>

          <div className="flex-1 overflow-auto">
            {loading ? (
              <div className="flex justify-center items-center h-full">
                <div className="border-gray-900 border-b-2 rounded-full w-8 h-8 animate-spin"></div>
              </div>
            ) : conversations.length === 0 ? (
              <div className="flex flex-col justify-center items-center p-4 h-full text-gray-500 text-center">
                <MessageSquare size={32} className="mb-2" />
                <p className="text-sm">No conversations found</p>
                {searchQuery && <p className="mt-1 text-xs">Try adjusting your search</p>}
              </div>
            ) : (
              conversations.map((conversation) => (
                <div
                  key={conversation.id}
                  className={`flex gap-4 cursor-pointer border-b py-2 px-3 hover:bg-primary/10 dark:hover:bg-primary/2 ${selectedConversation?.id === conversation.id ? 'bg-primary/10 dark:bg-primary/20' : ''
                    }`}
                >
                  <div className="flex items-center" onClick={(e) => e.stopPropagation()}>
                    <Checkbox
                      className="border-primary"
                      checked={selectedConversations.includes(conversation.id)}
                      onCheckedChange={(checked) => handleSelectConversation(conversation.id, checked as boolean)}
                    />
                  </div>
                  <div
                    className="flex-1 w-full overflow-hidden whitespace-nowrap mail-preview"
                    onClick={() => setSelectedConversation(conversation)}
                  >
                    <div className="flex justify-between items-start">
                      <span className="truncate font-semibold">{conversation.subject}</span>
                      <span className="text-gray-500 text-xs">
                        {formatDate(getLastMessage(conversation)?.created_at || conversation.created_at)}
                      </span>
                    </div>
                    <div className="text-gray-500 text-sm truncate">
                      {getLastMessage(conversation)?.sender_display_name ?
                        `${getLastMessage(conversation)?.sender_display_name}: ${getLastMessage(conversation)?.body}` :
                        getLastMessage(conversation)?.body || 'No messages'}
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>

        {/* Chat Conversation View */}
        <div className="flex flex-col flex-1">
          {selectedConversation ? (
            <>
              {/* Chat Header */}
              <div className="p-3 border-b">
                <div className="flex justify-between items-center">
                  <div>
                    <h2 className="font-semibold">{selectedConversation.subject}</h2>
                  </div>
                  <Button
                    variant="ghost"
                    size="sm"
                    className="text-red-500 hover:text-red-600 hover:bg-red-50"
                    onClick={() => handleMoveToTrash([selectedConversation.id])}
                  >
                    <Trash2 size={16} className="mr-1" />
                    <span>Delete</span>
                  </Button>
                </div>
              </div>

              {/* Chat Messages */}
              <div className="flex-1 space-y-3 bg-primary/10 dark:bg-primary/10 p-3 overflow-auto">
                {selectedConversation.notifications.map((msg) => {
                  const isCurrentUser = msg.sender_id === (auth as any).user.id;
                  const senderName = isCurrentUser
                    ? 'You'
                    : (msg.sender_display_name || `User ${msg.sender_id}`);

                  return (
                    <div key={msg.id} className={`flex ${isCurrentUser ? 'justify-end' : 'justify-start'}`}>
                      <div
                        className={`max-w-[70%] rounded-lg p-2 shadow-sm ${isCurrentUser
                            ? 'bg-primary text-white rounded-tr-none'
                            : 'bg-white dark:bg-gray-800 rounded-tl-none'
                          }`}
                      >
                        <div className={`text-xs mb-1 font-semibold ${isCurrentUser ? 'text-blue-100' : 'text-gray-500'}`}>
                          {senderName}
                        </div>
                        <p className="text-sm">{msg.body}</p>
                        <div className={`text-xs mt-1 ${isCurrentUser ? 'text-blue-100' : 'text-gray-500'}`}>
                          {formatTime(msg.created_at)}
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>

              {/* Chat Input */}
              <div className="bg-white dark:bg-gray-800 p-3 border-t">
                <div className="flex items-end gap-2">
                  <div className="flex-1">
                    <Textarea
                      placeholder="Type your message..."
                      className="min-h-[60px] max-h-[60px] resize-none"
                      value={replyMessage}
                      onChange={(e) => setReplyMessage(e.target.value)}
                      onKeyDown={(e) => {
                        if (e.key === 'Enter' && !e.shiftKey) {
                          e.preventDefault();
                          handleSendReply();
                        }
                      }}
                    />
                  </div>
                  <Button
                    variant="default"
                    size="icon"
                    className="rounded-full w-9 h-9"
                    onClick={handleSendReply}
                    disabled={replyMessage.trim() === ''}
                  >
                    <SendIcon size={16} />
                  </Button>
                </div>
              </div>
            </>
          ) : (
            <div className="flex flex-col justify-center items-center h-full text-gray-500">
              <MessageSquare size={48} className="mb-4" />
              <h3 className="mb-2 font-medium text-xl">Select a conversation</h3>
              <p>Choose a conversation from the list to view its contents</p>
            </div>
          )}
        </div>
      </div>
    </AppLayout>
  );
}
