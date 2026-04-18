<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->unsignedSmallInteger('platform_fee_rate_bps')
                ->nullable()
                ->comment('プラットフォーム手数料率（bps）')
                ->after('fincode_shop_id');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('platform_fee_rate_bps');
        });
    }
};
