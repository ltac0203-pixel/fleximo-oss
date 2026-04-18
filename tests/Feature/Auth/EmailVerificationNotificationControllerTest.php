<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EmailVerificationNotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_verification_notification_logs_without_trace(): void
    {
        $user = User::factory()->unverified()->create();

        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('Notification failure'));
        $this->app->instance(Dispatcher::class, $dispatcher);

        Log::spy();

        $response = $this->actingAs($user)
            ->from('/verify-email')
            ->post('/email/verification-notification');

        $response->assertRedirect('/verify-email');
        $response->assertSessionHas('status', 'verification-link-failed');

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context) use ($user): bool {
                return $message === 'Failed to send verification email'
                    && ($context['user_id'] ?? null) === $user->id
                    && ($context['error'] ?? null) === 'Notification failure'
                    && ($context['exception_class'] ?? null) === \RuntimeException::class
                    && ! array_key_exists('trace', $context);
            });
    }
}
