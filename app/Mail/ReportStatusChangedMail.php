<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportStatusChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Report $report
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Report Status Updated: ' . ucfirst($this->report->status),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reports.status-changed',
            with: [
                'report' => $this->report,
                'recipient' => $this->report->user,
                'reviewer' => $this->report->reviewer,
            ],
        );
    }
}
