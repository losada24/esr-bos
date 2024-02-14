<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliveredOrPickedUpChange extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
      protected Order $order,
      protected string $status,
      protected string $notes
    )
    {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $appName = config('app.name');
        $quoteNumber = '#' . $this->order->getQuoteNumberAttribute();
        return new Envelope(
            subject: "[$appName] Order $quoteNumber Update: " . strtoupper($this->status),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.delivered-or-picked-up-change',
            with: [
                'name' => $this->order->user->name,
                'quote_number' => $this->order->getQuoteNumberAttribute(),
                'status' => strtoupper($this->status),
                'updated_at' => $this->order->updated_at,
                'notes' => $this->notes,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
