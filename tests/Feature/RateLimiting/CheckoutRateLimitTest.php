<?php

declare(strict_types=1);

namespace Tests\Feature\RateLimiting;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('future')]
class CheckoutRateLimitTest extends TestCase
{
    use RefreshDatabase;

    // チェックアウトエンドポイントがレート制限されていることをテスト
    public function test_checkout_endpoint_is_rate_limited(): void
    {
        $tenant = Tenant::factory()->create();
        $customer = User::factory()->customer()->create();

        // Sanctumトークンで認証
        $token = $customer->createToken('test-token')->plainTextToken;

        // レート制限の閾値を超えるリクエストを送信
        // デフォルトのLaravelレート制限は60リクエスト/分
        $responses = [];
        for ($i = 0; $i < 62; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ])->withIdempotencyKey()
                ->postJson('/api/customer/checkout', [
                    'tenant_id' => $tenant->id,
                    'items' => [],
                ]);

            $responses[] = $response->getStatusCode();
        }

        // 最初の60リクエストは成功または422（バリデーションエラー）
        // 61リクエスト目以降は429（Too Many Requests）
        $tooManyRequestsResponses = array_filter($responses, fn ($status) => $status === 429);

        $this->assertGreaterThan(0, count($tooManyRequestsResponses), 'レート制限が適用されていません');
    }

    // 異なるユーザーは独立したレート制限を持つことをテスト
    public function test_different_users_have_independent_rate_limits(): void
    {
        $tenant = Tenant::factory()->create();
        $customer1 = User::factory()->customer()->create();
        $customer2 = User::factory()->customer()->create();

        $token1 = $customer1->createToken('test-token')->plainTextToken;
        $token2 = $customer2->createToken('test-token')->plainTextToken;

        // customer1が60リクエストを送信
        for ($i = 0; $i < 60; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer '.$token1,
                'Accept' => 'application/json',
            ])->withIdempotencyKey()
                ->postJson('/api/customer/checkout', [
                    'tenant_id' => $tenant->id,
                    'items' => [],
                ]);
        }

        // customer2は引き続きリクエスト可能であるべき
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token2,
            'Accept' => 'application/json',
        ])->postJson('/api/customer/checkout', [
            'tenant_id' => $tenant->id,
            'items' => [],
        ]);

        // 429ではないことを確認（レート制限されていない）
        $this->assertNotEquals(429, $response->getStatusCode());
    }

    // 認証なしのリクエストもレート制限されることをテスト
    public function test_unauthenticated_requests_are_rate_limited(): void
    {
        $tenant = Tenant::factory()->create();

        $responses = [];
        for ($i = 0; $i < 62; $i++) {
            $response = $this->withIdempotencyKey()
                ->postJson('/api/customer/checkout', [
                    'tenant_id' => $tenant->id,
                    'items' => [],
                ]);

            $responses[] = $response->getStatusCode();
        }

        // 401 Unauthorized または 429 Too Many Requests
        $tooManyRequestsResponses = array_filter($responses, fn ($status) => $status === 429);

        $this->assertGreaterThan(0, count($tooManyRequestsResponses), '未認証リクエストがレート制限されていません');
    }
}
