<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Report $report
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Report Created: ' . $this->report->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reports.new',
            with: [
                'report' => $this->report,
                'author' => $this->report->user,
                'department' => $this->report->department,
            ],
        );
    }
}
