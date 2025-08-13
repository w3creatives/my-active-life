<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Mailboxer\DeleteConversation;
use App\Actions\Mailboxer\GetConversation;
use App\Actions\Mailboxer\GetUserConversations;
use App\Actions\Mailboxer\MarkConversationAsRead;
use App\Actions\Mailboxer\MoveConversationToTrash;
use App\Actions\Mailboxer\ReplyToConversation;
use App\Actions\Mailboxer\RestoreConversationFromTrash;
use App\Actions\Mailboxer\SendMessage;
use App\Models\MailboxerConversation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class MailboxerController extends Controller
{
    public function index(Request $request, GetUserConversations $action)
    {
        $box = $request->query('box', 'inbox');
        $conversations = $action->handle(auth()->user(), $box);

        return Inertia::render('Mailboxer/Index', [
            'conversations' => $conversations,
            'box' => $box,
        ]);
    }

    public function create()
    {
        $recipients = User::where('id', '!=', auth()->id())->get();

        return Inertia::render('Mailboxer/Create', [
            'recipients' => $recipients,
        ]);
    }

    public function store(Request $request, SendMessage $action)
    {
        $validated = $request->validate([
            'recipients' => 'required|array',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $conversation = $action->handle(
            auth()->user(),
            $validated['recipients'],
            $validated['subject'],
            $validated['body']
        );

        return redirect()->route('mailboxer.show', $conversation);
    }

    public function show(MailboxerConversation $conversation, GetConversation $action)
    {
        $conversationData = $action->handle($conversation, auth()->user());

        return Inertia::render('Mailboxer/Show', [
            'conversation' => $conversationData,
        ]);
    }

    public function reply(Request $request, MailboxerConversation $conversation, ReplyToConversation $action)
    {
        $validated = $request->validate([
            'body' => 'required|string',
        ]);

        $action->handle(auth()->user(), $conversation, $validated['body']);

        return redirect()->route('mailboxer.show', $conversation);
    }

    public function markAsRead(MailboxerConversation $conversation, MarkConversationAsRead $action)
    {
        $action->handle($conversation, auth()->user());

        return redirect()->back();
    }

    public function moveToTrash(MailboxerConversation $conversation, MoveConversationToTrash $action)
    {
        $action->handle($conversation, auth()->user());

        return redirect()->route('mailboxer.index');
    }

    public function restoreFromTrash(MailboxerConversation $conversation, RestoreConversationFromTrash $action)
    {
        $action->handle($conversation, auth()->user());

        return redirect()->route('mailboxer.index', ['box' => 'trash']);
    }

    public function destroy(MailboxerConversation $conversation, DeleteConversation $action)
    {
        $action->handle($conversation, auth()->user());

        return redirect()->route('mailboxer.index');
    }
}
