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
use Barryvdh\DomPDF\PDF as DomPDFPDF;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;

class InstallationDateConfirmation extends Mailable implements ShouldQueue
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
      protected bool $supervisorAttachments = false,
      protected array $selectedOrderAttachmentIds = []

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
          $attachmentIds = collect($this->selectedOrderAttachmentIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

          if (!empty($attachmentIds)) {
            $orderAttachments = $this->order->attachments()
              ->whereIn('attachments.id', $attachmentIds)
              ->get();

            foreach ($orderAttachments as $attachment) {
              $attachments[] = Attachment::fromStorageDisk('public', $attachment->file_path);
            }
          }
        }

         /* if ($this->installationAttachments) {
          $numberorder = preg_replace('/[^A-Za-z0-9]/', '', $this->order->order_number);
          $pdfName = 'payment-list-' . $numberorder. '.pdf';
          $pdfPath = storage_path('app/public/pdf/' . $pdfName);
          if (Storage::disk('local')->exists($pdfPath)) {
            Storage::disk('local')->delete($pdfPath);
          }
          
          $pdf = Pdf::loadView('pdf.payment-list', ['order' => $this->order]);
          $pdf->save($pdfPath);
          $attachments[] = Attachment::fromPath($pdfPath);
        } */
         if ($this->installationAttachments) {
        $numberorder = preg_replace('/[^A-Za-z0-9]/', '', $this->order->order_number);
        $pdfName = 'payment-list-' . $numberorder . '.pdf';
        $pdfPath = 'pdf/' . $pdfName;

        // Eliminar si ya existe
        if (Storage::disk('public')->exists($pdfPath)) {
            Storage::disk('public')->delete($pdfPath);
        }

        // Generar PDF y guardarlo en el disco 'public' (S3 o local)
        $pdf = Pdf::loadView('pdf.payment-list', ['order' => $this->order]);
        Storage::disk('public')->put($pdfPath, $pdf->output());

        // Agregar como adjunto desde el disco
        $attachments[] = Attachment::fromStorageDisk('public', $pdfPath);
    }

        /*if ($this->supervisorAttachments) {
          $numberorder = preg_replace('/[^A-Za-z0-9]/', '', $this->order->order_number);
          $pdfName = 'supervisor-list-' . $numberorder . '.pdf';
          $pdfPath = storage_path('app/public/pdf/' . $pdfName);
          if (Storage::disk('local')->exists($pdfPath)) {
            Storage::disk('local')->delete($pdfPath);
          }
          
          $pdf = Pdf::loadView('pdf.supervisor-list', ['order' => $this->order]);
          $pdf->save($pdfPath);
          $attachments[] = Attachment::fromPath($pdfPath);
        }*/

        if ($this->supervisorAttachments) {
        $numberorder = preg_replace('/[^A-Za-z0-9]/', '', $this->order->order_number);
        $pdfName = 'supervisor-list-' . $numberorder . '.pdf';
        $pdfPath = 'pdf/' . $pdfName;

        if (Storage::disk('public')->exists($pdfPath)) {
            Storage::disk('public')->delete($pdfPath);
        }

        $pdf = Pdf::loadView('pdf.supervisor-list', ['order' => $this->order]);
        Storage::disk('public')->put($pdfPath, $pdf->output());

        $attachments[] = Attachment::fromStorageDisk('public', $pdfPath);
    }

        return $attachments;
    }
}
