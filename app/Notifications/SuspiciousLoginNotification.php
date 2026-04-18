<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\AuditAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspiciousLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array{type: AuditAction, metadata: array<string, mixed>}>  $anomalies
     */
    public function __construct(
        private readonly array $anomalies,
        private readonly string $ipAddress,
        private readonly string $userAgent,
        private readonly string $loginAt,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('不審なログインが検知されました')
            ->line('お客様のアカウントで通常とは異なるログインが検知されました。')
            ->line("ログイン日時: {$this->loginAt}")
            ->line("IPアドレス: {$this->ipAddress}")
            ->line("デバイス: {$this->userAgent}");

        foreach ($this->anomalies as $anomaly) {
            $label = $anomaly['type']->label();
            $mail->line("検知内容: {$label}");
        }

        return $mail
            ->line('この操作に心当たりがない場合は、直ちにパスワードを変更してください。')
            ->action('パスワードをリセットする', url(route('password.request')));
    }
}
