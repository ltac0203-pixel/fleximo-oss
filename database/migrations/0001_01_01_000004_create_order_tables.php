<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // orders テーブル
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('注文者');
            $table->unsignedBigInteger('tenant_id')->comment('注文先テナント');
            $table->unsignedBigInteger('payment_id')->nullable()->comment('決済ID');
            $table->char('order_code', 4)->comment('表示用注文番号（例: A123）');
            $table->date('business_date')->comment('営業日');
            $table->string('status', 20)->default('pending_payment')->comment('注文ステータス');
            $table->unsignedInteger('total_amount')->comment('合計金額');
            $table->timestamp('paid_at')->nullable()->comment('決済完了日時');
            $table->timestamp('accepted_at')->nullable()->comment('受付日時');
            $table->timestamp('in_progress_at')->nullable()->comment('調理開始日時');
            $table->timestamp('ready_at')->nullable()->comment('準備完了日時');
            $table->timestamp('completed_at')->nullable()->comment('完了日時');
            $table->timestamp('cancelled_at')->nullable()->comment('キャンセル日時');
            $table->timestamps();

            $table->unique(['tenant_id', 'business_date', 'order_code'], 'orders_tenant_business_code_unique');
            $table->index(['tenant_id', 'status'], 'orders_status_index');
            $table->index('payment_id', 'orders_payment_id_foreign');
            $table->index(['tenant_id', 'created_at'], 'orders_tenant_id_created_at_index');
            $table->index(['user_id', 'created_at'], 'orders_user_id_created_at_index');

            $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
        });

        // order_items テーブル
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('menu_item_id')->nullable()->comment('元メニュー商品ID（参照用）');
            $table->string('name')->comment('商品名（スナップショット）');
            $table->unsignedInteger('price')->comment('単価（スナップショット）');
            $table->unsignedSmallInteger('quantity')->comment('数量');
            $table->timestamp('created_at')->nullable();

            $table->index('order_id', 'order_items_order_id_index');
            $table->index('tenant_id', 'order_items_tenant_id_index');

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        // order_item_options テーブル
        Schema::create('order_item_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_item_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('option_id')->nullable()->comment('元オプションID（参照用）');
            $table->string('name', 100)->comment('オプション名（スナップショット）');
            $table->integer('price')->comment('追加価格（スナップショット）');
            $table->timestamp('created_at')->nullable();

            $table->index('order_item_id', 'order_item_options_order_item_id_index');
            $table->index('tenant_id', 'order_item_options_tenant_id_index');
            $table->index('option_id', 'order_item_options_option_id_foreign');

            $table->foreign('order_item_id')->references('id')->on('order_items')->cascadeOnDelete();
            $table->foreign('option_id')->references('id')->on('options')->nullOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // order_number_sequences テーブル
        Schema::create('order_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->date('business_date')->comment('営業日');
            $table->unsignedInteger('last_sequence')->default(0)->comment('最終シーケンス番号');
            $table->timestamps();

            $table->unique(['tenant_id', 'business_date'], 'order_number_sequences_unique');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_number_sequences');
        Schema::dropIfExists('order_item_options');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
