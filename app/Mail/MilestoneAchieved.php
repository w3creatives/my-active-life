<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

final class MilestoneAchieved extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public $user, public $milestone, public $event, public $emailTemplate) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailTemplate->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.milstones.achieved',
            with: [
                'content' => Str::of($this->emailTemplate->content)
                    ->replace('{{MILESTONE_NAME}}', $this->milestone->name)
                    ->replace('{{MILESTONE_DISTANCE}}', $this->milestone->distance)
                    ->replace('{{MILESTONE_IMAGE}}', sprintf('<img src="%s" alt="">', $this->milestone->logo ? $this->milestone->logo : '')),
            ],
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
}
