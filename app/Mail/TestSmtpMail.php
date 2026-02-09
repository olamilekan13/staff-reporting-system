<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestSmtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SMTP Configuration Test - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.test-smtp',
            with: [
                'recipient' => $this->recipient,
            ],
        );
    }
}
