<?php

namespace App\Mail;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class RequestStandBy extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected ?string $tempAttachmentPath = null;

    public function __construct(protected Order $order)
    {
    }

    protected function ensureRelationsLoaded(): void
    {
        $this->order->loadMissing([
            'user'
            
        ]);
    }
    public function envelope(): Envelope
    {
        $appName = config('app.name');

        return new Envelope(
            subject: "Order assigned to standby status[$appName]",
        );
    }

    public function content(): Content
    {
        $this->ensureRelationsLoaded();

        return new Content(
            view: 'emails.request-standby',
            with: [
                'order' => $this->order,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    /*public function __destruct()
    {
        if ($this->tempAttachmentPath) {
            Storage::disk('public')->delete($this->tempAttachmentPath);
        }
    }*/
}
