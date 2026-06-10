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

class OrderOwnerAssigned extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected ?string $tempAttachmentPath = null;

    public function __construct(
        protected Order $order,
        protected string $recipientName,
        protected array $currentOwnerNames = []
    ) {
    }

    protected function ensureRelationsLoaded(): void
    {
        $this->order->loadMissing([
            'saleForm',
            'client.companyContact',
            'owners',
            'orderNotes.user',
        ]);
    }

    public function envelope(): Envelope
    {
        $this->ensureRelationsLoaded();

        return new Envelope(
            subject: 'Order reassigned to you - ' . ($this->order->name ?? ('Order #' . $this->order->id)),
        );
    }

    public function content(): Content
    {
        $this->ensureRelationsLoaded();

        return new Content(
            view: 'emails.order-owner-assigned',
            with: [
                'order' => $this->order,
                'recipientName' => $this->recipientName,
                'currentOwnerNames' => $this->currentOwnerNames,
            ],
        );
    }

    public function attachments(): array
    {
        $this->ensureRelationsLoaded();

        if (!$this->order->saleForm) {
            return [];
        }

        $filename = 'sale-form-' . ($this->order->order_number ?? $this->order->id) . '.pdf';
        $tempPath = 'tmp/' . uniqid('sale-form-') . '.pdf';

        Storage::disk('public')->makeDirectory('tmp');

        $pdf = Pdf::loadView('pdf.sale-form', [
            'order' => $this->order,
        ]);
        Storage::disk('public')->put($tempPath, $pdf->output());

        $this->tempAttachmentPath = $tempPath;

        return [
            Attachment::fromStorageDisk('public', $tempPath)
                ->as($filename)
                ->withMime('application/pdf'),
        ];
    }

    public function __destruct()
    {
        if ($this->tempAttachmentPath) {
            Storage::disk('public')->delete($this->tempAttachmentPath);
        }
    }
}
