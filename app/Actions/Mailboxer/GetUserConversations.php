<?php

declare(strict_types=1);

namespace App\Actions\Mailboxer;

use App\Models\User;
use App\Services\MailboxerService;
use Lorisleiva\Actions\Concerns\AsAction;

final class GetUserConversations
{
    use AsAction;

    protected MailboxerService $mailboxerService;

    public function __construct(MailboxerService $mailboxerService)
    {
        $this->mailboxerService = $mailboxerService;
    }

    public function handle(User $user, string $boxType = 'inbox'): array
    {
        $conversations = $this->mailboxerService->getUserConversations($user, $boxType);

        return $this->mailboxerService->formatConversations($conversations, $user);
    }
}
