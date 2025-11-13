<?php

declare(strict_types=1);

namespace App\Mail\Campaign;

use App\Models\Auth\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for goal achieved for campaign notifications.
 * Sent to campaign creator when the goal is achieved.
 */
class CampaignGoalAchievedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param User $receiver The campaign creator receiving the notification
     * @param array<string, mixed> $campaign The achieved campaign.
     */
    public function __construct(
        public readonly User $receiver,
        public readonly array $campaign
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your goal has been achieved for campaign: ' . $this->campaign['title'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.campaign.goal-achieved',
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
