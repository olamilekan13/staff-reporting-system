<?php

namespace App\Mail;

use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewCommentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Comment $comment,
        public User $recipient
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Comment on Your ' . $this->getResourceType(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.comments.new',
            with: [
                'comment' => $this->comment,
                'recipient' => $this->recipient,
                'commenter' => $this->comment->user,
                'resourceType' => $this->getResourceType(),
                'resourceTitle' => $this->comment->commentable->title,
            ],
        );
    }

    private function getResourceType(): string
    {
        return $this->comment->commentable instanceof Report ? 'Report' : 'Proposal';
    }
}
