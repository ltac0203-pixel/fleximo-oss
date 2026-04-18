<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OrderNumberGenerator;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderNumberConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private const MAX_INSERT_RETRIES = 20;

    private OrderNumberGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new OrderNumberGenerator;
    }

    public function test_高速連続作成でユニーク制約競合を吸収できる(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $businessDate = Carbon::today();
        $codes = [];

        for ($i = 0; $i < 100; $i++) {
            $codes[] = $this->createOrderWithRetry($tenant, $user, $businessDate);
        }

        $uniqueCodes = array_unique($codes);
        $this->assertCount(100, $uniqueCodes, '重複する注文番号が生成されました');

        // A123形式（アルファベット1文字+数字3桁）であることを確認
        foreach ($codes as $code) {
            $this->assertEquals(4, strlen($code), "注文番号の長さが4文字ではありません: {$code}");
            $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ]\d{3}$/', $code, "注文番号の形式が不正です: {$code}");
        }
    }

    public function test_d_b_ユニーク制約による重複防止確認(): void
    {
        $tenant = Tenant::factory()->create();
        $businessDate = Carbon::today();

        $orderCode = $this->generator->generate($tenant->id, $businessDate);

        $user = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'order_code' => $orderCode,
            'business_date' => $businessDate,
            'total_amount' => 1000,
            'status' => 'pending_payment',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_code' => $orderCode,
        ]);

        $this->expectException(QueryException::class);

        Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'order_code' => $orderCode,
            'business_date' => $businessDate,
            'total_amount' => 2000,
            'status' => 'pending_payment',
        ]);
    }

    public function test_複数テナント同時生成で独立採番(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $tenant3 = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $businessDate = Carbon::today();

        $codesPerTenant = [];

        foreach ([$tenant1, $tenant2, $tenant3] as $tenant) {
            for ($i = 0; $i < 30; $i++) {
                $codesPerTenant[$tenant->id][] = $this->createOrderWithRetry($tenant, $user, $businessDate);
            }
        }

        foreach ([$tenant1, $tenant2, $tenant3] as $tenant) {
            $codes = $codesPerTenant[$tenant->id];

            // 各テナントで30個のユニークな注文番号が生成されることを確認
            $this->assertCount(30, array_unique($codes), "テナント{$tenant->id}で重複が発生しました");

            // A123形式（アルファベット1文字+数字3桁）であることを確認
            foreach ($codes as $code) {
                $this->assertEquals(4, strlen($code), "テナント{$tenant->id}の注文番号の長さが4文字ではありません: {$code}");
                $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ]\d{3}$/', $code, "テナント{$tenant->id}の注文番号の形式が不正です: {$code}");
            }
        }
    }

    public function test_異なる営業日は独立して採番(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $day1 = Carbon::today();
        $day2 = Carbon::tomorrow();

        $codesDay1 = [];
        $codesDay2 = [];

        for ($i = 0; $i < 10; $i++) {
            $codesDay1[] = $this->createOrderWithRetry($tenant, $user, $day1);
            $codesDay2[] = $this->createOrderWithRetry($tenant, $user, $day2);
        }

        // 各営業日で10個のユニークな注文番号が生成されることを確認
        $this->assertCount(10, array_unique($codesDay1), '1日目で重複が発生しました');
        $this->assertCount(10, array_unique($codesDay2), '2日目で重複が発生しました');

        // A123形式（アルファベット1文字+数字3桁）であることを確認
        foreach ($codesDay1 as $code) {
            $this->assertEquals(4, strlen($code), "1日目の注文番号の長さが4文字ではありません: {$code}");
            $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ]\d{3}$/', $code, "1日目の注文番号の形式が不正です: {$code}");
        }
        foreach ($codesDay2 as $code) {
            $this->assertEquals(4, strlen($code), "2日目の注文番号の長さが4文字ではありません: {$code}");
            $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ]\d{3}$/', $code, "2日目の注文番号の形式が不正です: {$code}");
        }
    }

    public function test_大量生成でも重複なし(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $businessDate = Carbon::today();

        $codes = [];
        for ($i = 0; $i < 1000; $i++) {
            $codes[] = $this->createOrderWithRetry($tenant, $user, $businessDate);
        }

        // 1000個すべてがユニークであることを確認
        $this->assertCount(1000, array_unique($codes), '大量生成時に重複が発生しました');

        // A123形式（アルファベット1文字+数字3桁）であることを確認
        foreach ($codes as $code) {
            $this->assertEquals(4, strlen($code), "注文番号の長さが4文字ではありません: {$code}");
            $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ]\d{3}$/', $code, "注文番号の形式が不正です: {$code}");
        }
    }

    private function createOrderWithRetry(Tenant $tenant, User $user, Carbon $businessDate): string
    {
        for ($attempt = 1; $attempt <= self::MAX_INSERT_RETRIES; $attempt++) {
            $code = $this->generator->generate($tenant->id, $businessDate);

            try {
                Order::factory()->create([
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'order_code' => $code,
                    'business_date' => $businessDate,
                    'total_amount' => 100,
                    'status' => 'pending_payment',
                ]);

                return $code;
            } catch (QueryException $e) {
                if (! $this->isDuplicateOrderCodeError($e)) {
                    throw $e;
                }
            }
        }

        $this->fail('注文番号の競合リトライが上限に達しました。');
        throw new \RuntimeException('注文番号の競合リトライが上限に達しました。');
    }

    private function isDuplicateOrderCodeError(QueryException $e): bool
    {
        $code = (string) $e->getCode();
        $message = $e->getMessage();

        return $code === '23000'
            || str_contains($message, '1062')
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'UNIQUE constraint failed');
    }
}
