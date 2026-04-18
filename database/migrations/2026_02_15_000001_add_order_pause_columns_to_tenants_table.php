<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('is_order_paused')->default(false)->after('is_active')->comment('注文受付一時停止フラグ');
            $table->timestamp('order_paused_at')->nullable()->after('is_order_paused')->comment('注文受付一時停止開始日時');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['is_order_paused', 'order_paused_at']);
        });
    }
};
