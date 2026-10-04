<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportMessageReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly string $conversationId,
        public readonly string $supportMessage,
        public readonly string $conversationUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New support message from '.$this->customerName);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.support.new-message',
            with: [
                'customerName' => $this->customerName,
                'customerEmail' => $this->customerEmail,
                'conversationId' => $this->conversationId,
                'supportMessage' => $this->supportMessage,
                'conversationUrl' => $this->conversationUrl,
            ],
        );
    }
}
