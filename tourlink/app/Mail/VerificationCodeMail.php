<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly int $expiresInMinutes,
        public readonly bool $registration = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->registration
            ? 'Welcome to '.config('app.name').' - your verification code'
            : 'Your '.config('app.name').' verification code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.verification-code',
            with: [
                'name' => $this->name,
                'code' => $this->code,
                'expiresInMinutes' => $this->expiresInMinutes,
                'registration' => $this->registration,
            ],
        );
    }
}
