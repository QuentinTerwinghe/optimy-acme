<?php

declare(strict_types=1);

namespace App\Mail\Campaign;

use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for campaign waiting for validation notifications.
 * Sent to campaign managers when a campaign status changes to waiting for validation.
 */
class CampaignWaitingForValidationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param User $receiver The campaign manager receiving the notification
     * @param array<string, mixed> $campaign The campaign waiting for validation
     * @param User $creator The user who created/submitted the campaign
     */
    public function __construct(
        public readonly User $receiver,
        public readonly array $campaign,
        public readonly User $creator
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Campaign Awaiting Validation: ' . $this->campaign['title'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.campaign.waiting-for-validation',
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
