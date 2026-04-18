<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('パスワードリセットのご案内')
            ->line('パスワードリセットのご依頼を受け付けました。')
            ->action('パスワードをリセットする', $url)
            ->line("このリンクは {$expireMinutes} 分で期限切れになります。")
            ->line('パスワードリセットをご依頼していない場合は、このメールを無視してください。');
    }
}
