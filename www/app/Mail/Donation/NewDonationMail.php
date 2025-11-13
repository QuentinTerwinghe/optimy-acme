<?php

declare(strict_types=1);

namespace App\Mail\Donation;

use App\Models\Auth\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for new donation notifications.
 * Sent to campaign creator when a new donation is made.
 */
class NewDonationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param User $receiver The donation creator receiving the notification
     * @param array<string, mixed> $campaign The campaign.
     * @param array<string, mixed> $donation The donation.
     */
    public function __construct(
        public readonly User $receiver,
        public readonly array $campaign,
        public readonly array $donation,
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A new donation has been made: ' . $this->campaign['title'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.donation.new-donation',
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
