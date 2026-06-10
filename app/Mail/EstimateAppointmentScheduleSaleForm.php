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

class EstimateAppointmentScheduleSaleForm extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected ?string $tempAttachmentPath = null;

    public function __construct(protected Order $order)
    {
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
        $clientName = optional($this->order->client)->name ?? 'Unknown client';

        return new Envelope(
            subject: "Estimate & appointment schedule assigned - {$clientName}",
        );
    }

    public function content(): Content
    {
        $this->ensureRelationsLoaded();
        $orderNotes = $this->order->orderNotes->sortBy('created_at');

        return new Content(
            view: 'emails.estimate-appointment-schedule',
            with: [
                'order' => $this->order,
                'orderNotes' => $orderNotes,
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
