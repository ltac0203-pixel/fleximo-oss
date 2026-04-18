<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    // お問い合わせフォームメールを作成する
    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $contactSubject,
        public string $contactMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from_addresses.contact.address'),
                config('mail.from_addresses.contact.name'),
            ),
            replyTo: [
                new Address($this->senderEmail, $this->senderName),
            ],
            subject: "[お問い合わせ] {$this->contactSubject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
