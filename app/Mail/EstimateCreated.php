<?php

namespace App\Mail;

use App\Models\Order;
use App\Traits\Fractions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Traits\Marks;

class EstimateCreated extends Mailable
{
    use Queueable, SerializesModels, Marks, Fractions;

    /**
     * Create a new message instance.
     */
    public function __construct(
      protected Order $order,
      protected array $role
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $appName = config('app.name');
        $quoteNumber = '#' . $this->order->getQuoteNumberAttribute();
        return new Envelope(
            subject: "[$appName] Estimate $quoteNumber Created",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.estimate-created',
            with: [
              'name' => $this->order->user->name,
              'quote_number' => $this->createMarkWithLeadingZero($this->order->id, 6),
              'created_at' => $this->order->created_at,
              'order' => $this->order,
              'role' => $this->role
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
