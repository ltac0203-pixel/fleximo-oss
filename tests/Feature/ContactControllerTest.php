<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    public function test_mail_send_failure_does_not_log_email_address(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \RuntimeException('SMTP unavailable'));

        Log::spy();

        $response = $this->from('/contact')->post('/contact', [
            'name' => 'Taro Yamada',
            'email' => 'customer@example.com',
            'subject' => 'Need support',
            'message' => 'Please help.',
        ]);

        $response->assertRedirect('/contact');
        $response->assertSessionHas('error', 'メールの送信に失敗しました。しばらく時間をおいて再度お試しください。');

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'お問い合わせメールのキュー追加に失敗しました'
                    && ($context['subject'] ?? null) === 'Need support'
                    && ($context['error'] ?? null) === 'SMTP unavailable'
                    && ($context['exception_class'] ?? null) === \RuntimeException::class
                    && ! array_key_exists('email', $context);
            });
    }
}
