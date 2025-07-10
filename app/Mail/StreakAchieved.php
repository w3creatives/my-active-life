<?php

declare(strict_types=1);

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

final class StreakAchieved extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public $user, public $streak, public $event, public $emailTemplate, public $accomplishedDate)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: Str::of($this->emailTemplate->subject)
                ->replace('{{EVENT_NAME}}', $this->event->name)
                ->toString(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.streaks.achieved',
            with: [
                'content' => Str::of($this->emailTemplate->content)
                    ->replace('{{STREAK_NAME}}', $this->streak->name)
                    ->replace('{{ACCOMPLISHED_DATE}}', Carbon::parse($this->accomplishedDate)->format('m/d/Y'))
                    ->replace('{{STREAK_IMAGE}}', $this->streak->logo ? sprintf('<img src="%s" alt="">', $this->streak->logo ? $this->streak->logo : '') : '')
                    ->replace('{{USER_NAME}}', $this->user->full_name),
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
