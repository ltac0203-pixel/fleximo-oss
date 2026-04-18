<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // carts テーブル
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('ユーザー');
            $table->unsignedBigInteger('tenant_id')->comment('対象テナント');
            $table->timestamps();

            $table->unique(['user_id', 'tenant_id'], 'carts_user_tenant_unique');
            $table->index('tenant_id', 'carts_tenant_id_index');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // cart_items テーブル
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('menu_item_id');
            $table->unsignedSmallInteger('quantity')->default(1)->comment('数量');
            $table->timestamps();

            $table->index('cart_id', 'cart_items_cart_id_index');
            $table->index('menu_item_id', 'cart_items_menu_item_id_index');
            $table->index('tenant_id', 'cart_items_tenant_id_index');

            $table->foreign('cart_id')->references('id')->on('carts')->cascadeOnDelete();
            $table->foreign('menu_item_id')->references('id')->on('menu_items')->cascadeOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // cart_item_options テーブル
        Schema::create('cart_item_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_item_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('option_id');
            $table->timestamp('created_at')->nullable();

            $table->unique(['cart_item_id', 'option_id'], 'cart_item_options_unique');
            $table->index('option_id', 'cart_item_options_option_id_index');
            $table->index('tenant_id', 'cart_item_options_tenant_id_index');

            $table->foreign('cart_item_id')->references('id')->on('cart_items')->cascadeOnDelete();
            $table->foreign('option_id')->references('id')->on('options')->cascadeOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_item_options');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
