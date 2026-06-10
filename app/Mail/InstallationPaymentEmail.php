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
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class InstallationPaymentEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
      protected Collection  $orders,
      protected string $installerName,
      protected string $companyName,
      protected string $biweeklyTitle,

    ){}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
      $appName = config('app.name');
      $biweekly= $this->biweeklyTitle;
        return new Envelope(
          subject: "Biweekly payment proposal [$biweekly]",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.installation-payment',
            with: [
              'order' => $this->orders,
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
          $numberorder = preg_replace('/[^A-Za-z0-9]/', '', $this->biweeklyTitle);
          $installerName = preg_replace('/[^A-Za-z0-9]/', '', $this->installerName);
          $pdfName = 'pdf.payment-list-orders-' . $numberorder.''. $installerName. '.pdf';
          $pdfPath = 'pdf/' . $pdfName;
          if (Storage::disk('public')->exists($pdfPath)) {
            Storage::disk('public')->delete($pdfPath);
          }
          
          $pdf = Pdf::loadView('pdf.payment-list-orders', ['orders' => $this->orders, 'installer' => $this->installerName, 'company' => $this->companyName,'biweeklyTitle' => $this->biweeklyTitle])->setPaper('A2', 'landscape');
          Storage::disk('public')->put($pdfPath, $pdf->output());
          $attachments[] = Attachment::fromStorageDisk('public', $pdfPath);
        

        return $attachments;
    }
}
