<?php

namespace App\Mail;

use App\Models\Order;
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
        if ($this->clientAttachments) {
            $electronicTransferPath = resource_path('assets/files/ELECTRONIC_TRANSFER.pdf');
            $attachments[] = Attachment::fromPath($electronicTransferPath);
            $authorizationFormPath = resource_path('assets/files/Reylos_Authorization_Form_Final_Updated.pdf');
            $attachments[] = Attachment::fromPath($authorizationFormPath);
        }
        return $attachments;
    }
}
