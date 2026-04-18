<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->json('old_values')->nullable()->comment('変更前の値')->change();
            $table->json('new_values')->nullable()->comment('変更後の値')->change();
            $table->json('metadata')->nullable()->comment('追加メタデータ')->change();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->longText('old_values')->nullable()->comment('変更前の値')->change();
            $table->longText('new_values')->nullable()->comment('変更後の値')->change();
            $table->longText('metadata')->nullable()->comment('追加メタデータ')->change();
        });
    }
};
