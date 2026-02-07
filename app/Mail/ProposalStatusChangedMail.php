<?php

namespace App\Mail;

use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProposalStatusChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Proposal $proposal
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Proposal Status Updated: ' . ucfirst(str_replace('_', ' ', $this->proposal->status)),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.proposals.status-changed',
            with: [
                'proposal' => $this->proposal,
                'recipient' => $this->proposal->user,
                'reviewer' => $this->proposal->reviewer,
            ],
        );
    }
}
