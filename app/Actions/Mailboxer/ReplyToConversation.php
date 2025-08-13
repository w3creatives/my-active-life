<?php

declare(strict_types=1);

namespace App\Actions\Mailboxer;

use App\Models\MailboxerConversation;
use App\Models\MailboxerNotification;
use App\Models\MailboxerReceipt;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

final class ReplyToConversation
{
    use AsAction;

    public function handle(User $sender, MailboxerConversation $conversation, string $body): MailboxerNotification
    {
        // Create a reply notification
        $notification = MailboxerNotification::create([
            'subject' => '',
            'body' => $body,
            'sender_type' => class_basename($sender),
            'sender_id' => $sender->id,
            'conversation_id' => $conversation->id,
        ]);

        // Add sender's receipt (sent folder)
        MailboxerReceipt::create([
            'notification_id' => $notification->id,
            'receiver_type' => class_basename($sender),
            'receiver_id' => $sender->id,
            'mailbox_type' => 'sent',
            'is_read' => true,
        ]);

        // Add recipient receipts (for all participants except sender)
        $participants = $conversation->participants()->where(function ($query) use ($sender) {
            $query->where('receiver_id', '!=', $sender->id)
                ->orWhere('receiver_type', '!=', class_basename($sender));
        })->get();

        foreach ($participants as $receipt) {
            MailboxerReceipt::create([
                'notification_id' => $notification->id,
                'receiver_type' => $receipt->receiver_type,
                'receiver_id' => $receipt->receiver_id,
                'mailbox_type' => 'inbox',
            ]);
        }

        return $notification;
    }
}
