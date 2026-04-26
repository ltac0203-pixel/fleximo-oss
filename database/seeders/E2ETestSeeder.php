<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class E2ETestSeeder extends Seeder
{
    // E2Eテストが前提とするテナント・ユーザー・注文データを一括投入する。
    // 各テストケースが共通の既知状態から開始できるようにするためのSeeder。
    public function run(): void
    {
        // メニューやカテゴリはテナントに依存するため、テナント→カテゴリ→商品の順で先に投入する。
        $this->call([
            TenantSeeder::class,
            MenuCategorySeeder::class,
            MenuItemSeeder::class,
        ]);

        // E2Eテストで固定的に使うテナントを特定する。slugは他Seederと合わせた既知値。
        $tenant = Tenant::where('slug', 'cafe-bluesky')->firstOrFail();

        // 注文フロー・カート操作のE2Eテストに使う顧客アカウント。updateOrCreateで冪等性を担保。
        $customer = User::updateOrCreate(
            ['email' => 'e2e-customer@example.com'],
            [
                'name' => 'E2Eテスト顧客',
                'role' => UserRole::Customer,
                'email_verified_at' => now(),
                'password' => 'password',
            ]
        );

        // KDS（キッチンディスプレイシステム）画面・注文管理のE2Eテストに使うスタッフアカウント。
        $staff = User::updateOrCreate(
            ['email' => 'e2e-staff@example.com'],
            [
                'name' => 'E2Eテストスタッフ',
                'role' => UserRole::TenantStaff,
                'email_verified_at' => now(),
                'password' => 'password',
            ]
        );

        // ダッシュボード・メニュー管理のE2Eテストに使う管理者アカウント。
        $admin = User::updateOrCreate(
            ['email' => 'e2e-admin@example.com'],
            [
                'name' => 'E2Eテスト管理者',
                'role' => UserRole::TenantAdmin,
                'email_verified_at' => now(),
                'password' => 'password',
            ]
        );

        // テナントスコープの認可が通るよう、スタッフとテナントの所属関係を構築する。
        TenantUser::updateOrCreate(
            ['tenant_id' => $tenant->id, 'user_id' => $staff->id],
            ['role' => TenantUserRole::Staff]
        );

        // 管理者も同様にテナントへ紐付け。Adminロールでのアクセス制御テストに必要。
        TenantUser::updateOrCreate(
            ['tenant_id' => $tenant->id, 'user_id' => $admin->id],
            ['role' => TenantUserRole::Admin]
        );

        // KDS（キッチンディスプレイシステム）画面に「受付済み」注文が表示されるテストシナリオのため、Accepted状態の注文を作成する。
        $menuItem = MenuItem::where('tenant_id', $tenant->id)
            ->where('name', 'コーヒー')
            ->firstOrFail();

        $order = Order::updateOrCreate(
            ['order_code' => 'E2E-001', 'tenant_id' => $tenant->id],
            [
                'user_id' => $customer->id,
                'status' => OrderStatus::Accepted,
                'business_date' => now()->toDateString(),
                'total_amount' => 350,
                'accepted_at' => now(),
            ]
        );

        OrderItem::updateOrCreate(
            ['order_id' => $order->id, 'tenant_id' => $tenant->id, 'name' => 'コーヒー'],
            [
                'menu_item_id' => $menuItem->id,
                'price' => 350,
                'quantity' => 1,
            ]
        );

        // KDSのフルライフサイクルE2EテストでPaid→Acceptedの「受付」操作を検証するため、
        // Accepted注文（E2E-001）とは別に、Paid状態の注文（E2E-002）を投入する。
        $paidOrder = Order::updateOrCreate(
            ['order_code' => 'E2E-002', 'tenant_id' => $tenant->id],
            [
                'user_id' => $customer->id,
                'status' => OrderStatus::Paid,
                'business_date' => now()->toDateString(),
                'total_amount' => 700,
                'paid_at' => now(),
            ]
        );

        OrderItem::updateOrCreate(
            ['order_id' => $paidOrder->id, 'tenant_id' => $tenant->id, 'name' => 'コーヒー'],
            [
                'menu_item_id' => $menuItem->id,
                'price' => 350,
                'quantity' => 2,
            ]
        );
    }
}
