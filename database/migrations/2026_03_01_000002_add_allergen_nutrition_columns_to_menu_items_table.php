<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->unsignedInteger('allergens')->default(0)->comment('特定原材料8品目ビットマスク');
            $table->unsignedInteger('allergen_advisories')->default(0)->comment('推奨表示20品目ビットマスク');
            $table->text('allergen_note')->nullable()->comment('アレルゲン自由記述（コンタミ注意等）');
            $table->longText('nutrition_info')->nullable()->comment('栄養成分JSON（energy,protein,fat,carbohydrate,salt）');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn(['allergens', 'allergen_advisories', 'allergen_note', 'nutrition_info']);
        });
    }
};
