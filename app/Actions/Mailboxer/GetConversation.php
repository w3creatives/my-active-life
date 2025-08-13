<?php

declare(strict_types=1);

namespace App\Actions\Mailboxer;

use App\Models\MailboxerConversation;
use App\Models\User;
use App\Services\MailboxerService;
use Lorisleiva\Actions\Concerns\AsAction;

final class GetConversation
{
    use AsAction;

    protected MailboxerService $mailboxerService;

    public function __construct(MailboxerService $mailboxerService)
    {
        $this->mailboxerService = $mailboxerService;
    }

    public function handle(MailboxerConversation $conversation, User $user): array
    {
        // Mark conversation as read
        $conversation->markAsRead($user);

        // Get conversation with messages
        return $this->mailboxerService->getConversations($user);
    }
}
