<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification as ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'Password1',
                'password_confirmation' => 'Password1',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_existing_session_is_invalidated_after_password_reset(): void
    {
        $user = User::factory()->create();
        $oldPasswordHash = $user->getAuthPassword();

        // パスワードを変更
        $user->forceFill([
            'password' => 'new-password',
            'remember_token' => Str::random(60),
        ])->save();

        // セッションに旧パスワードハッシュが残っている状態を再現し、
        // AuthenticateSession ミドルウェアが不一致を検知してログアウトさせることを検証
        $this->actingAs($user, 'web')
            ->withSession(['password_hash_web' => $oldPasswordHash])
            ->get(route('profile.edit'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_all_sessions_are_immediately_deleted_on_password_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        // 複数セッションを挿入（ログイン中デバイスを模擬）
        $sessionId1 = 'fake-session-'.Str::random(10);
        $sessionId2 = 'fake-session-'.Str::random(10);
        DB::table('sessions')->insert([
            [
                'id' => $sessionId1,
                'user_id' => $user->id,
                'ip_address' => '192.168.1.1',
                'user_agent' => 'TestBrowser/1.0',
                'payload' => base64_encode(serialize([])),
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => $sessionId2,
                'user_id' => $user->id,
                'ip_address' => '192.168.1.2',
                'user_agent' => 'TestBrowser/2.0',
                'payload' => base64_encode(serialize([])),
                'last_activity' => now()->timestamp,
            ],
        ]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'NewPassword1',
                'password_confirmation' => 'NewPassword1',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });

        // 全セッションが即時削除されること
        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
    }
}
