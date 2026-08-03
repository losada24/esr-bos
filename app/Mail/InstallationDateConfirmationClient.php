<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\OrderPhase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class InstallationDateConfirmationClient extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
      protected Order $order,
      //protected bool $displaySummary = false,
      protected bool $clientAttachments = false,
      protected array $selectedOrderAttachmentIds = [],
      protected ?OrderPhase $phase = null,
      //protected bool $installationAttachments = false
    ){}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
      $appName = config('app.name');
        return new Envelope(
          subject: " Confirmed delivery and installation dates. [$appName]",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.installation-date-confirmation-client',
            with: [
              'order' => $this->order,
              'phase' => $this->phase,
              //'displaySummary' => $this->displaySummary,
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

        if ($this->clientAttachments) {
            $electronicTransferPath = resource_path('assets/files/ELECTRONIC_TRANSFER.pdf');
            $attachments[] = Attachment::fromPath($electronicTransferPath);
            $authorizationFormPath = resource_path('assets/files/Reylos_Authorization_Form_Final_Updated.pdf');
            $attachments[] = Attachment::fromPath($authorizationFormPath);
        }
        return $attachments;
    }
}
