<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InstallationDateConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
      protected Order $order,
      protected bool $displaySummary = false,
      protected bool $orderAttachments = false,
      protected bool $installationAttachments = false,
      protected bool $supervisorAttachments = false

    ){}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
      $appName = config('app.name');
        return new Envelope(
          subject: "Installation date confirmation. [$appName]",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.installation-date-confirmation',
            with: [
              'order' => $this->order,
              'displaySummary' => $this->displaySummary,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        if ($this->orderAttachments) {
          foreach ($this->order->attachments as $attachment) {
            $attachments[] = Attachment::fromPath(storage_path('app/public/' . $attachment->file_path));
          }
        }

        if ($this->installationAttachments) {
          $pdfName = 'payment-list-' . $this->order->order_number . '.pdf';
          $pdfPath = storage_path('app/public/pdf/' . $pdfName);
          if (Storage::disk('local')->exists($pdfPath)) {
            Storage::disk('local')->delete($pdfPath);
          }
          
          $pdf = Pdf::loadView('pdf.payment-list', ['order' => $this->order]);
          $pdf->save($pdfPath);
          $attachments[] = Attachment::fromPath($pdfPath);
        }

        if ($this->supervisorAttachments) {
          $pdfName = 'supervisor-list-' . $this->order->order_number . '.pdf';
          $pdfPath = storage_path('app/public/pdf/' . $pdfName);
          if (Storage::disk('local')->exists($pdfPath)) {
            Storage::disk('local')->delete($pdfPath);
          }
          
          $pdf = Pdf::loadView('pdf.supervisor-list', ['order' => $this->order]);
          $pdf->save($pdfPath);
          $attachments[] = Attachment::fromPath($pdfPath);
        }

        return $attachments;
    }
}
