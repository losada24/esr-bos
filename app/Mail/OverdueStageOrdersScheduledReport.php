<?php

namespace App\Mail;

use App\Exports\OverdueStageOrdersScheduledEmailExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class OverdueStageOrdersScheduledReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public array $data)
    {
        $this->subject = 'Overdue Stage Orders Report';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Overdue Stage Orders Report'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.overdue-stage-orders-scheduled-report',
            with: $this->data
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => Pdf::loadView('pdf.overdue-stage-orders-scheduled-email', $this->data)
                    ->setPaper('A4', 'landscape')
                    ->output(),
                'Overdue Stage Orders.pdf'
            )->withMime('application/pdf'),
            Attachment::fromData(
                fn () => Excel::raw(
                    new OverdueStageOrdersScheduledEmailExport($this->data),
                    \Maatwebsite\Excel\Excel::XLSX
                ),
                'Overdue Stage Orders.xlsx'
            )->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
