<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class MailboxerService
{
    public function getConversations(User $user)
    {
        $conversations = $user->mailboxerConversations()->get()->toArray();

        return $this->formatConversation($conversations);
    }

    private function formatConversation($conversations)
    {
        return collect($conversations)->map(function ($conversation) {
            // Sort notifications by created_at ASC
            $sortedNotifications = collect($conversation['notifications'])
                ->sortBy('created_at')
                ->values();

            // Get the latest notification (last after sorting by ASC)
            $latestNotification = $sortedNotifications->last();

            // Filter notifications to keep only required fields
            $notifications = $sortedNotifications->map(function ($notification) {
                // Get sender's display name
                $sender = null;
                if ($notification['sender_id']) {
                    $sender = User::find($notification['sender_id']);
                }

                return [
                    'id' => $notification['id'],
                    'body' => $notification['body'],
                    'sender_id' => $notification['sender_id'],
                    'sender_display_name' => $sender ? $sender->name : null,
                    'created_at' => $notification['created_at'],
                    'attachment' => $notification['attachment'],
                    'type' => $notification['type'],
                    'receipts' => $notification['receipts'],
                ];
            })->values();

            return [
                'id' => $conversation['id'],
                'subject' => $conversation['subject'],
                'created_at' => $conversation['created_at'],
                'updated_at' => $conversation['updated_at'],
                'last_message' => $latestNotification ? [
                    'body' => $latestNotification['body'],
                    'created_at' => $latestNotification['created_at'],
                    'sender_id' => $latestNotification['sender_id'],
                    'sender_display_name' => User::find($latestNotification['sender_id'])?->name,
                ] : null,
                'notifications' => $notifications,
            ];
        })->values();
    }
}
