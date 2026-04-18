<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // menu_categories テーブル
        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 100)->comment('カテゴリ名');
            $table->string('slug', 150)->comment('URLスラッグ');
            $table->text('description')->nullable()->comment('説明');
            $table->string('image_url', 500)->nullable()->comment('画像URL');
            $table->integer('sort_order')->default(0)->comment('表示順');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();

            $table->unique(['tenant_id', 'slug'], 'menu_categories_tenant_slug_unique');
            $table->index(['tenant_id', 'sort_order'], 'menu_categories_sort_order_index');
            $table->index(['tenant_id', 'is_active'], 'menu_categories_is_active_index');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // menu_items テーブル
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 100)->comment('商品名');
            $table->text('description')->nullable()->comment('説明');
            $table->unsignedInteger('price')->default(0)->comment('価格（税込）');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->boolean('is_sold_out')->default(false)->comment('売切フラグ');
            $table->unsignedTinyInteger('available_days')->default(127)->comment('販売曜日ビットマスク');
            $table->time('available_from')->nullable()->comment('販売開始時刻');
            $table->time('available_until')->nullable()->comment('販売終了時刻');
            $table->integer('sort_order')->default(0)->comment('表示順');
            $table->timestamps();

            $table->index(['tenant_id', 'sort_order'], 'menu_items_sort_order_index');
            $table->index('tenant_id', 'menu_items_tenant_id_index');
            $table->index(['tenant_id', 'is_active'], 'menu_items_tenant_id_is_active_index');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // menu_item_categories テーブル
        Schema::create('menu_item_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_item_id');
            $table->unsignedBigInteger('menu_category_id');
            $table->integer('sort_order')->default(0)->comment('カテゴリ内表示順');
            $table->timestamp('created_at')->nullable();

            $table->unique(['menu_item_id', 'menu_category_id'], 'menu_item_categories_unique');
            $table->index('menu_category_id', 'menu_item_categories_category_id_foreign');

            $table->foreign('menu_item_id')->references('id')->on('menu_items')->cascadeOnDelete();
            $table->foreign('menu_category_id', 'menu_item_categories_category_id_foreign')->references('id')->on('menu_categories')->cascadeOnDelete();
        });

        // option_groups テーブル
        Schema::create('option_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 100)->comment('グループ名（例: サイズ、トッピング）');
            $table->boolean('required')->default(false)->comment('必須選択フラグ');
            $table->unsignedTinyInteger('min_select')->default(0)->comment('最小選択数');
            $table->unsignedTinyInteger('max_select')->default(1)->comment('最大選択数');
            $table->integer('sort_order')->default(0)->comment('表示順');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tenant_id', 'option_groups_tenant_id_index');
            $table->index(['tenant_id', 'is_active'], 'option_groups_tenant_id_is_active_index');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // options テーブル
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('option_group_id');
            $table->string('name', 100)->comment('オプション名（例: Mサイズ、Lサイズ）');
            $table->integer('price')->default(0)->comment('追加価格（マイナス値可）');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->integer('sort_order')->default(0)->comment('表示順');
            $table->timestamps();

            $table->index('option_group_id', 'options_option_group_id_index');
            $table->index(['option_group_id', 'is_active'], 'options_option_group_id_is_active_index');

            $table->foreign('option_group_id')->references('id')->on('option_groups')->cascadeOnDelete();
        });

        // menu_item_option_groups テーブル
        Schema::create('menu_item_option_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_item_id');
            $table->unsignedBigInteger('option_group_id');
            $table->integer('sort_order')->default(0)->comment('表示順');
            $table->timestamp('created_at')->nullable();

            $table->unique(['menu_item_id', 'option_group_id'], 'menu_item_option_groups_unique');
            $table->index('option_group_id', 'menu_item_option_groups_option_group_id_foreign');

            $table->foreign('menu_item_id')->references('id')->on('menu_items')->cascadeOnDelete();
            $table->foreign('option_group_id')->references('id')->on('option_groups')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_option_groups');
        Schema::dropIfExists('options');
        Schema::dropIfExists('option_groups');
        Schema::dropIfExists('menu_item_categories');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menu_categories');
    }
};
