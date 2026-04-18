<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // キュージョブの最大試行回数
    public int $tries = 3;

    // 試行間のバックオフ秒数
    public array $backoff = [10, 60, 300];

    // 注文完了通知メールを作成する
    public function __construct(
        public Order $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name'),
            ),
            subject: '[Fleximo] ご注文が完了しました',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-completed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
