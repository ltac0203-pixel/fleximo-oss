<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'NewPassword1',
                'password_confirmation' => 'NewPassword1',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('NewPassword1', $user->refresh()->password));
    }

    public function test_remember_token_is_regenerated_on_password_update(): void
    {
        $user = User::factory()->create([
            'remember_token' => 'old-remember-token',
        ]);

        $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'NewPassword1',
                'password_confirmation' => 'NewPassword1',
            ]);

        $this->assertNotEquals('old-remember-token', $user->refresh()->remember_token);
        $this->assertNotNull($user->remember_token);
        $this->assertEquals(60, strlen($user->remember_token));
    }

    public function test_other_session_is_invalidated_after_password_update(): void
    {
        config(['session.driver' => 'database']);

        $user = User::factory()->create();

        // 別セッション（攻撃者のブラウザ等）でログイン状態を作る
        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk();

        // パスワード変更（別のリクエストとして実行）
        $user->forceFill([
            'password' => 'new-password',
            'remember_token' => Str::random(60),
        ])->save();

        // 元のセッションでアクセスすると、パスワードハッシュ不一致により自動ログアウトされる
        $this->get(route('profile.edit'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'NewPassword1',
                'password_confirmation' => 'NewPassword1',
            ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect('/profile');
    }

    public function test_password_changed_notification_is_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'NewPassword1',
                'password_confirmation' => 'NewPassword1',
            ]);

        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }

    public function test_password_changed_notification_is_not_sent_on_failure(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'NewPassword1',
                'password_confirmation' => 'NewPassword1',
            ]);

        Notification::assertNotSentTo($user, PasswordChangedNotification::class);
    }

    public function test_other_sessions_are_immediately_deleted_on_password_update(): void
    {
        $user = User::factory()->create();

        // 別デバイスのセッションを挿入
        $otherSessionId = 'fake-session-'.Str::random(10);
        DB::table('sessions')->insert([
            'id' => $otherSessionId,
            'user_id' => $user->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'TestBrowser/1.0',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);

        $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'NewPassword1',
                'password_confirmation' => 'NewPassword1',
            ]);

        // 他セッションが即時削除されること
        $this->assertDatabaseMissing('sessions', ['id' => $otherSessionId]);
    }
}
