<?php

namespace App\Mail;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewAnnouncementMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Announcement $announcement,
        public User $recipient
    ) {}

    public function envelope(): Envelope
    {
        $prefix = $this->announcement->priority === Announcement::PRIORITY_URGENT ? '[URGENT] ' : '';

        return new Envelope(
            subject: $prefix . 'New Announcement: ' . $this->announcement->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.announcements.new',
            with: [
                'announcement' => $this->announcement,
                'recipient' => $this->recipient,
            ],
        );
    }
}
