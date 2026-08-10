<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OverdueStageOrdersReportMail extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public $subject;

  public function __construct(
    private readonly string $generatedAt,
    private readonly array $totals,
    private readonly array $groups,
    private readonly string $pdfPath,
    private readonly string $excelPath,
  ) {
    $this->subject = '[' . config('app.name') . '] Overdue Stage Orders Report - ' . now('America/New_York')->format('Y-m-d');
  }

  public function envelope(): Envelope
  {
    return new Envelope(
      subject: $this->subject,
    );
  }

  public function content(): Content
  {
    return new Content(
      view: 'emails.overdue-stage-orders-report',
      with: [
        'generatedAt' => $this->generatedAt,
        'totals' => $this->totals,
        'groups' => $this->groups,
      ],
    );
  }

  public function attachments(): array
  {
    return [
      Attachment::fromStorageDisk('public', $this->pdfPath)
        ->as('overdue-stage-orders.pdf')
        ->withMime('application/pdf'),
      Attachment::fromStorageDisk('public', $this->excelPath)
        ->as('Overdue Stage Orders.xlsx')
        ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
    ];
  }
}
