<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->dropUnique('webhook_logs_fincode_id_unique');
            $table->unique(
                ['provider', 'event_type', 'fincode_id'],
                'webhook_logs_provider_event_fincode_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->dropUnique('webhook_logs_provider_event_fincode_unique');
            $table->unique('fincode_id', 'webhook_logs_fincode_id_unique');
        });
    }
};
