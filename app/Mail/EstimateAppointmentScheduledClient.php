<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EstimateAppointmentScheduledClient extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(protected Order $order)
    {
    }

    protected function ensureRelationsLoaded(): void
    {
        $this->order->loadMissing([
            'client',
            'owners',
        ]);
    }

    public function envelope(): Envelope
    {
        $this->ensureRelationsLoaded();

        return new Envelope(
            subject: 'Consultation Confirmation – Reylos Glass',
        );
    }

    public function content(): Content
    {
        $this->ensureRelationsLoaded();

        return new Content(
            view: 'emails.estimate-appointment-client',
            with: [
                'order' => $this->order,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
