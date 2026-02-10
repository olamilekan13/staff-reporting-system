<?php

namespace App\Mail;

use App\Models\Comment;
use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewCommentForAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Comment $comment
    ) {}

    public function envelope(): Envelope
    {
        $resourceType = $this->comment->commentable instanceof Report ? 'Report' : 'Proposal';
        $resourceTitle = $this->comment->commentable->title;

        return new Envelope(
            subject: "New Comment on {$resourceType}: {$resourceTitle}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.comments.new-admin',
            with: [
                'comment' => $this->comment,
                'commenter' => $this->comment->user,
                'resourceType' => $this->comment->commentable instanceof Report ? 'Report' : 'Proposal',
                'resourceTitle' => $this->comment->commentable->title,
                'resource' => $this->comment->commentable,
            ],
        );
    }
}
