<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Services\OrderNumberGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private OrderNumberGenerator $generator;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new OrderNumberGenerator;
        $this->tenant = Tenant::factory()->create(['is_active' => true]);
    }

    public function test_generate_アルファベット1文字と数字3桁が生成される(): void
    {
        $businessDate = Carbon::today();

        $orderCode = $this->generator->generate($this->tenant->id, $businessDate);

        // アルファベット1文字＋数字3桁であることを確認
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ][0-9]{3}$/', $orderCode);
        $this->assertEquals(4, strlen($orderCode));
    }

    public function test_generate_複数回生成しても形式が正しい(): void
    {
        $businessDate = Carbon::today();

        // 同一テナント・営業日で複数の注文番号を生成
        for ($i = 0; $i < 10; $i++) {
            $code = $this->generator->generate($this->tenant->id, $businessDate);

            $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ][0-9]{3}$/', $code);
            $this->assertEquals(4, strlen($code));
        }
    }

    public function test_generate_先頭アルファベットに紛らわしい文字は使用されない(): void
    {
        $businessDate = Carbon::today();

        // 複数回生成して、先頭アルファベットに除外文字（O, I, L）が含まれないことを確認
        for ($i = 0; $i < 50; $i++) {
            $code = $this->generator->generate($this->tenant->id, $businessDate);

            // 先頭アルファベットに紛らわしい文字が含まれていないことを確認
            $firstChar = $code[0];
            $this->assertNotEquals('O', $firstChar);
            $this->assertNotEquals('I', $firstChar);
            $this->assertNotEquals('L', $firstChar);

            // 形式が正しいことを確認
            $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ][0-9]{3}$/', $code);
        }
    }

    public function test_generate_異なる営業日では同じ注文番号を使用できる(): void
    {
        $day1 = Carbon::today();
        $day2 = Carbon::tomorrow();

        $code1 = $this->generator->generate($this->tenant->id, $day1);

        // day1の注文番号を保存
        \Illuminate\Support\Facades\DB::table('orders')->insert([
            'tenant_id' => $this->tenant->id,
            'business_date' => $day1->toDateString(),
            'order_code' => $code1,
            'total_amount' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // day2でも注文番号を生成（ランダムなので異なる可能性が高いが、同じでもエラーにならないことを確認）
        $code2 = $this->generator->generate($this->tenant->id, $day2);

        // 両方とも4文字の英数字であることを確認
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ][0-9]{3}$/', $code1);
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ][0-9]{3}$/', $code2);
    }

    public function test_generate_異なるテナントは独立して採番(): void
    {
        $tenant2 = Tenant::factory()->create(['is_active' => true]);
        $businessDate = Carbon::today();

        $code1 = $this->generator->generate($this->tenant->id, $businessDate);
        $code2 = $this->generator->generate($tenant2->id, $businessDate);

        // 両方とも4文字の英数字であることを確認
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ][0-9]{3}$/', $code1);
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ][0-9]{3}$/', $code2);
    }

    public function test_generate_営業日未指定でも生成できる(): void
    {
        $orderCode = $this->generator->generate($this->tenant->id);

        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ][0-9]{3}$/', $orderCode);
        $this->assertEquals(4, strlen($orderCode));
    }

    public function test_get_business_date_深夜0時から5時前は前日の営業日を返す(): void
    {

        Carbon::setTestNow(Carbon::create(2026, 1, 20, 2, 0, 0, 'Asia/Tokyo'));

        $businessDate = $this->generator->getBusinessDate();

        $this->assertEquals('2026-01-19', $businessDate->toDateString());
    }

    public function test_get_business_date_5時以降は当日の営業日を返す(): void
    {

        Carbon::setTestNow(Carbon::create(2026, 1, 20, 5, 0, 0, 'Asia/Tokyo'));

        $businessDate = $this->generator->getBusinessDate();

        $this->assertEquals('2026-01-20', $businessDate->toDateString());
    }

    public function test_get_business_date_昼間は当日の営業日を返す(): void
    {

        Carbon::setTestNow(Carbon::create(2026, 1, 20, 12, 0, 0, 'Asia/Tokyo'));

        $businessDate = $this->generator->getBusinessDate();

        $this->assertEquals('2026-01-20', $businessDate->toDateString());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
