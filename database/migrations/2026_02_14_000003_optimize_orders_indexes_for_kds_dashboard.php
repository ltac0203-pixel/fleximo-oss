<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 旧インデックス（tenant_id, status）を更新し、updated_at 条件の差分取得に最適化する。
        Schema::whenTableHasIndex('orders', ['tenant_id', 'status'], function (Blueprint $table): void {
            $table->dropIndex('orders_status_index');
        });

        Schema::whenTableDoesntHaveIndex(
            'orders',
            ['tenant_id', 'status', 'updated_at'],
            function (Blueprint $table): void {
                $table->index(['tenant_id', 'status', 'updated_at'], 'orders_status_index');
            }
        );

        // 日別集計で使う tenant_id + business_date + status 条件を効率化する。
        Schema::whenTableDoesntHaveIndex(
            'orders',
            ['tenant_id', 'business_date', 'status'],
            function (Blueprint $table): void {
                $table->index(
                    ['tenant_id', 'business_date', 'status'],
                    'orders_tenant_business_date_status_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::whenTableHasIndex(
            'orders',
            ['tenant_id', 'business_date', 'status'],
            function (Blueprint $table): void {
                $table->dropIndex('orders_tenant_business_date_status_index');
            }
        );

        Schema::whenTableHasIndex(
            'orders',
            ['tenant_id', 'status', 'updated_at'],
            function (Blueprint $table): void {
                $table->dropIndex('orders_status_index');
            }
        );

        Schema::whenTableDoesntHaveIndex('orders', ['tenant_id', 'status'], function (Blueprint $table): void {
            $table->index(['tenant_id', 'status'], 'orders_status_index');
        });
    }
};
