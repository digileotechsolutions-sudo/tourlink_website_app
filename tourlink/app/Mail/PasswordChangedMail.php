<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $name) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your '.config('app.name').' password was changed');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.password-changed',
            with: ['name' => $this->name],
        );
    }
}
