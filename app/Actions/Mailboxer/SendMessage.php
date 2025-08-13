<?php

declare(strict_types=1);

namespace App\Actions\Mailboxer;

use App\Models\MailboxerConversation;
use App\Models\MailboxerNotification;
use App\Models\MailboxerReceipt;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

final class SendMessage
{
    use AsAction;

    public function handle(User $sender, User $receiver, string $subject, string $body): MailboxerNotification
    {
        $conversation = MailboxerConversation::create(['subject' => $subject]);

        $notification = MailboxerNotification::create([
            'type' => 'Mailboxer::Message',
            'subject' => $subject,
            'body' => $body,
            'sender_type' => class_basename(get_class($sender)),
            'sender_id' => $sender->id,
            'conversation_id' => $conversation->id,
        ]);

        MailboxerReceipt::create([
            'notification_id' => $notification->id,
            'receiver_type' => class_basename(get_class($sender)),
            'receiver_id' => $sender->id,
            'mailbox_type' => 'sentbox',
            'is_read' => true,
        ]);

        MailboxerReceipt::create([
            'notification_id' => $notification->id,
            'receiver_type' => class_basename(get_class($sender)),
            'receiver_id' => $receiver->id,
            'mailbox_type' => 'inbox',
        ]);

        return $notification;
    }
}
