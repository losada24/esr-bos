<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderOwnerAdditionalOwnerAssigned extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        protected Order $order,
        protected string $recipientName,
        protected array $addedOwnerNames = [],
        protected array $currentOwnerNames = []
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Another seller was added to your order - ' . ($this->order->name ?? ('Order #' . $this->order->id)),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-owner-additional-owner-assigned',
            with: [
                'order' => $this->order,
                'recipientName' => $this->recipientName,
                'addedOwnerNames' => $this->addedOwnerNames,
                'currentOwnerNames' => $this->currentOwnerNames,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
