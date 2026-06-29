<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactOwnerAssigned extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        protected Client $client,
        protected string $recipientName,
        protected bool $isNewContact = false,
    ) {
    }

    protected function ensureRelationsLoaded(): void
    {
        $this->client->loadMissing([
            'user:id,name,email',
            'createdByUser:id,name,email',
            'companyContact',
            'companyContacts',
            'clientAddress',
            'referral.referrerClient:id,name,phone,email',
            'referral.referrerUser:id,name,phone,email',
        ]);
    }

    public function envelope(): Envelope
    {
        $action = $this->isNewContact ? 'New contact assigned' : 'Contact owner updated';

        return new Envelope(
            subject: sprintf(
                '[%s] %s - %s',
                config('app.name'),
                $action,
                $this->client->name ?: ('Contact #' . $this->client->id)
            ),
        );
    }

    public function content(): Content
    {
        $this->ensureRelationsLoaded();

        return new Content(
            view: 'emails.contact-owner-assigned',
            with: [
                'client' => $this->client,
                'recipientName' => $this->recipientName,
                'isNewContact' => $this->isNewContact,
                'contactUrl' => route('client.edit', $this->client),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
