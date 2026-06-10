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

class RequestReSchedule extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected ?string $tempAttachmentPath = null;

    public function __construct(
        protected Order $order,
        protected ?string $note = null
    )
    {
    }

    protected function ensureRelationsLoaded(): void
    {
        $this->order->loadMissing([
            'owners',
        ]);
    }
    public function envelope(): Envelope
    {
        $appName = config('app.name');

        return new Envelope(
            subject: "Order moved to REQUEST RE-SCHEDULE status [$appName]",
        );
    }

    public function content(): Content
    {
        $this->ensureRelationsLoaded();

        return new Content(
            view: 'emails.request-reschedule',
            with: [
                'order' => $this->order,
                'note' => $this->note,
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
