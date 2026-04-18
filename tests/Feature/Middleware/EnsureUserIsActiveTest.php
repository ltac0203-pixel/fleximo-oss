<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureUserIsActiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'active'])->get('/protected', function () {
            return 'Protected Content';
        });

        Route::middleware(['auth:sanctum', 'active'])->get('/api/protected', function () {
            return response()->json(['message' => 'API Content']);
        });

        Route::middleware(['web', 'active'])->get('/tenant/protected', function () {
            return 'Tenant Content';
        });
    }

    public function test_active_user_can_access_protected_route(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->get('/protected');

        $response->assertOk();
        $response->assertSee('Protected Content');
    }

    public function test_inactive_user_is_logged_out_and_redirected(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->actingAs($user)->get('/protected');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_inactive_user_sees_error_message(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->actingAs($user)->get('/protected');

        $response->assertSessionHas('error');
    }

    public function test_guest_can_access_protected_route(): void
    {

        $response = $this->get('/protected');

        $response->assertOk();
    }

    public function test_inactive_user_current_api_token_is_revoked(): void
    {
        $user = User::factory()->inactive()->create();
        $token = $user->createToken('test-token');

        $this->assertCount(1, $user->tokens);

        // APIトークン認証でアクセスした場合、現在のトークンのみ削除される
        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/protected');

        $user->refresh();
        $this->assertCount(0, $user->tokens);
    }

    public function test_inactive_user_gets_json_401_on_api_request(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->actingAs($user)->getJson('/api/protected');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'アカウントが無効化されています。管理者にお問い合わせください。',
        ]);
    }

    public function test_inactive_user_other_tokens_are_not_deleted_by_middleware(): void
    {
        $user = User::factory()->inactive()->create();
        $currentToken = $user->createToken('current-token');
        $user->createToken('other-token');

        $this->assertCount(2, $user->tokens);

        // 現在のトークンのみ削除され、他のトークンは残る
        // （全トークン削除はService層の無効化処理で実施する責務）
        $this->withHeader('Authorization', 'Bearer '.$currentToken->plainTextToken)
            ->getJson('/api/protected');

        $user->refresh();
        $this->assertCount(1, $user->tokens);
    }

    public function test_inactive_user_session_auth_does_not_delete_tokens(): void
    {
        $user = User::factory()->inactive()->create();
        $user->createToken('test-token');

        $this->assertCount(1, $user->tokens);

        // セッション認証ではトークン削除は不要（セッション無効化で十分）
        $this->actingAs($user)->get('/protected');

        $user->refresh();
        $this->assertCount(1, $user->tokens);
    }

    public function test_active_user_tokens_are_not_revoked(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->createToken('test-token');

        $this->actingAs($user)->get('/protected');

        $user->refresh();
        $this->assertCount(1, $user->tokens);
    }

    public function test_inactive_tenant_user_redirects_to_business_login(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->actingAs($user)->get('/tenant/protected');

        $response->assertRedirect(route('for-business.login'));
    }
}
