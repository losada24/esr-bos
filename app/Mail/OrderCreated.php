<?php

namespace App\Mail;

use App\Models\Order;
use App\Traits\Marks;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCreated extends Mailable
{
    use Queueable, SerializesModels, Marks;

    /**
     * Create a new message instance.
     */
    public function __construct(
      protected Order $order
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $appName = config('app.name');
        $quoteNumber = '#' . $this->order->getQuoteNumberAttribute();
        return new Envelope(
            subject: "[$appName] Estimate $quoteNumber Status Update: Order Created",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order-created',
            with: [
              'name' => $this->order->user->name,
              'quote_number' => $this->order->getQuoteNumberAttribute(),
              'updated_at' => $this->order->updated_at,
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
        return [];
    }
}
