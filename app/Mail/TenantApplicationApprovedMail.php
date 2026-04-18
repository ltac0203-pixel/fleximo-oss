<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantApplicationApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // キュージョブの最大試行回数
    public int $tries = 3;

    // 試行間のバックオフ秒数
    public array $backoff = [10, 60, 300];

    public ?string $passwordResetUrl = null;

    public string $loginUrl;

    public bool $requiresPasswordReset;

    // テナント申し込み承認通知メールを作成する
    public function __construct(
        public TenantApplication $application,
        public Tenant $tenant,
        public User $user,
        ?string $token = null
    ) {
        $this->requiresPasswordReset = $token !== null;

        if ($token) {
            $this->passwordResetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], false));
        }

        $this->loginUrl = url(route('login', [], false));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name'),
            ),
            subject: '[Fleximo] テナント申し込みが承認されました',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-application-approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
