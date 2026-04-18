<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('future')]
class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    // スタッフ削除操作が監査ログに記録されることをテスト
    // 注意: activity_logテーブル/Spatie ActivityLogパッケージ未導入のためスキップ
    public function test_staff_deletion_is_logged(): void
    {
        $this->markTestSkipped('Spatie ActivityLog パッケージ未導入のためスキップ');
    }

    public function test_order_cancellation_is_logged(): void
    {
        $this->markTestSkipped('Spatie ActivityLog パッケージ未導入のためスキップ');
    }

    public function test_menu_item_update_is_logged(): void
    {
        $this->markTestSkipped('Spatie ActivityLog パッケージ未導入のためスキップ');
    }

    public function test_sensitive_information_is_not_logged(): void
    {
        $this->markTestSkipped('Spatie ActivityLog パッケージ未導入のためスキップ');
    }

    public function test_privilege_escalation_is_logged(): void
    {
        $this->markTestSkipped('Spatie ActivityLog パッケージ未導入のためスキップ');
    }
}
