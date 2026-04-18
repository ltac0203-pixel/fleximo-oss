<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::whenTableDoesntHaveIndex('payments', 'payments_order_id_index', function (Blueprint $table): void {
            $table->index('order_id', 'payments_order_id_index');
        });
    }

    public function down(): void
    {
        Schema::whenTableHasIndex('payments', 'payments_order_id_index', function (Blueprint $table): void {
            $table->dropIndex('payments_order_id_index');
        });
    }
};
