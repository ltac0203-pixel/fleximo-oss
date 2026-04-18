<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\Admin\CustomerDataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDataExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerDataExportService $service;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CustomerDataExportService::class);
        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->customer()->create([
            'is_active' => true,
        ]);
    }

    public function test_build_export_data_structure(): void
    {
        $this->actingAs($this->admin);

        $data = $this->service->buildExportData($this->customer);

        $this->assertArrayHasKey('exported_at', $data);
        $this->assertArrayHasKey('profile', $data);
        $this->assertArrayHasKey('orders', $data);
        $this->assertArrayHasKey('favorites', $data);
    }

    public function test_export_excludes_sensitive_fields(): void
    {
        $this->actingAs($this->admin);

        $data = $this->service->buildExportData($this->customer);
        $profile = $data['profile'];

        $this->assertArrayNotHasKey('password', $profile);
        $this->assertArrayNotHasKey('remember_token', $profile);
        $this->assertArrayNotHasKey('fincode_customer_id', $profile);
        $this->assertArrayNotHasKey('fincode_card_id', $profile);
        $this->assertArrayNotHasKey('fincode_id', $profile);
        $this->assertArrayNotHasKey('fincode_access_id', $profile);
    }

    public function test_csv_sanitization_prevents_injection(): void
    {
        // sanitizeCsvValue はプライベートメソッドだが、sanitizeCsvRow 経由でテスト可能
        // ReflectionMethod を使用してプライベートメソッドを直接テストする
        $reflection = new \ReflectionMethod($this->service, 'sanitizeCsvValue');
        $reflection->setAccessible(true);

        // 危険な先頭文字がエスケープされることを確認（先頭にシングルクォートが付与される）
        $this->assertEquals("'=cmd|/C calc", $reflection->invoke($this->service, '=cmd|/C calc'));
        $this->assertEquals("'+cmd|/C calc", $reflection->invoke($this->service, '+cmd|/C calc'));
        $this->assertEquals("'-cmd|/C calc", $reflection->invoke($this->service, '-cmd|/C calc'));
        $this->assertEquals("'@cmd|/C calc", $reflection->invoke($this->service, '@cmd|/C calc'));

        // 通常の文字列はそのまま返される
        $this->assertEquals('normal value', $reflection->invoke($this->service, 'normal value'));

        // 数値はそのまま返される
        $this->assertEquals(12345, $reflection->invoke($this->service, 12345));
    }
}
