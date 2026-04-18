<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('パスワード変更のお知らせ')
            ->line('お客様のアカウントのパスワードが変更されました。')
            ->line('この操作に心当たりがない場合は、直ちにパスワードリセットを行ってください。')
            ->action('パスワードをリセットする', url(route('password.request')))
            ->line('ご不明な点がございましたら、サポートまでお問い合わせください。');
    }
}
