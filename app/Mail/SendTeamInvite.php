<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class SendTeamInvite extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $invitedUser,
        public Team $team,
        public User $inviter
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to join {$this->team->name} team!",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.team-invite',
            with: [
                'invitedUser' => $this->invitedUser,
                'team' => $this->team,
                'inviter' => $this->inviter,
                'acceptUrl' => route('teams.accept-invite', [
                    'team_id' => $this->team->id,
                    'user_id' => $this->invitedUser->id,
                    'token' => $this->generateInviteToken(),
                ]),
                'declineUrl' => route('teams.decline-invite', [
                    'team_id' => $this->team->id,
                    'user_id' => $this->invitedUser->id,
                    'token' => $this->generateInviteToken(),
                ]),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Generate a simple invite token for security
     */
    private function generateInviteToken(): string
    {
        return hash('sha256', $this->team->id.$this->invitedUser->id.config('app.key'));
    }
}
