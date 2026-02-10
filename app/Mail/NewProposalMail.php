<?php

namespace App\Mail;

use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewProposalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Proposal $proposal
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Proposal Created: ' . $this->proposal->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.proposals.new',
            with: [
                'proposal' => $this->proposal,
                'author' => $this->proposal->user,
            ],
        );
    }
}
