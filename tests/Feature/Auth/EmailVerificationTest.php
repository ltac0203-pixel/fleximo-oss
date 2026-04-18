<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_unauthenticated_customer_verification_redirects_to_login(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => UserRole::Customer,
        ]);

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect('/login');
        $response->assertSessionHas('status', 'メールアドレスの認証が完了しました。ログインしてください。');
    }

    public function test_unauthenticated_tenant_admin_verification_redirects_to_business_login(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => UserRole::TenantAdmin,
        ]);

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect('/for-business/login');
        $response->assertSessionHas('status', 'メールアドレスの認証が完了しました。ログインしてください。');
    }

    public function test_unauthenticated_tenant_staff_verification_redirects_to_business_login(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => UserRole::TenantStaff,
        ]);

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect('/for-business/login');
        $response->assertSessionHas('status', 'メールアドレスの認証が完了しました。ログインしてください。');
    }

    public function test_unauthenticated_verification_with_invalid_hash_returns_403(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $response = $this->get($verificationUrl);

        $response->assertStatus(403);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_unauthenticated_verification_with_nonexistent_user_returns_403(): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => 99999, 'hash' => sha1('test@example.com')]
        );

        $response = $this->get($verificationUrl);

        $response->assertStatus(403);
    }

    public function test_logged_in_user_cannot_verify_another_users_email(): void
    {
        $target = User::factory()->unverified()->create();
        $otherUser = User::factory()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $target->id, 'hash' => sha1($target->getEmailForVerification())]
        );

        $response = $this->actingAs($otherUser)->get($verificationUrl);

        $response->assertStatus(403);
        $this->assertFalse($target->fresh()->hasVerifiedEmail());
    }
}
