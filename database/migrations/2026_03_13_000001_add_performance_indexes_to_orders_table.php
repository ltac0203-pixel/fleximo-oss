<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // aggregateDailySalesDirect: 日付指定のクロステナント集計用
            $table->index(['business_date', 'status'], 'orders_business_date_status_index');
            // getCustomerInsights: NOT EXISTS による期間前注文の存在チェック用
            $table->index(['tenant_id', 'user_id', 'business_date'], 'orders_tenant_user_business_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_business_date_status_index');
            $table->dropIndex('orders_tenant_user_business_date_index');
        });
    }
};
