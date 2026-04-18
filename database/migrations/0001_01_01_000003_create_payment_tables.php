<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // payments テーブル
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('provider', 50)->default('fincode')->comment('決済プロバイダー');
            $table->string('method', 20)->comment('決済方法');
            $table->string('status', 20)->default('pending')->comment('決済ステータス');
            $table->unsignedInteger('amount')->comment('決済金額');
            $table->string('fincode_id', 100)->nullable()->comment('fincode決済ID');
            $table->string('fincode_access_id', 100)->nullable()->comment('fincodeアクセスID');
            $table->string('fincode_customer_id')->nullable()->comment('fincode顧客ID');
            $table->string('redirect_url', 500)->nullable()->comment('リダイレクトURL（PayPay/3Dセキュア）');
            $table->string('tds_trans_result', 50)->nullable()->comment('3DS認証結果');
            $table->string('tds_challenge_url', 500)->nullable()->comment('3DSチャレンジURL');
            $table->string('fincode_card_id', 100)->nullable()->comment('fincode カードID');
            $table->string('error_code', 50)->nullable()->comment('エラーコード');
            $table->text('error_message')->nullable()->comment('エラーメッセージ');
            $table->longText('metadata')->nullable()->comment('追加メタデータ');
            $table->timestamp('completed_at')->nullable()->comment('決済完了日時');
            $table->timestamps();

            $table->index('order_id', 'payments_order_id_index');
            $table->index('tenant_id', 'payments_tenant_id_index');
        });

        // fincode_customers テーブル
        Schema::create('fincode_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('fincode_customer_id', 100)->index('fincode_customers_fincode_customer_id_index');
            $table->timestamps();

            $table->unique(['user_id', 'tenant_id'], 'fincode_customers_user_id_tenant_id_unique');
            $table->index('tenant_id', 'fincode_customers_tenant_id_foreign');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // fincode_cards テーブル
        Schema::create('fincode_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fincode_customer_id');
            $table->string('fincode_card_id', 100)->index('fincode_cards_fincode_card_id_index');
            $table->string('card_no_display', 20);
            $table->string('brand', 20)->nullable();
            $table->string('expire', 10);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('fincode_customer_id', 'fincode_cards_fincode_customer_id_foreign');

            $table->foreign('fincode_customer_id')->references('id')->on('fincode_customers')->cascadeOnDelete();
        });

        // webhook_logs テーブル
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->comment('対象テナント');
            $table->string('provider', 50)->comment('プロバイダー（fincode）');
            $table->string('fincode_id', 100)->nullable()->unique('webhook_logs_fincode_id_unique')->comment('fincode固有ID（冪等性チェック用）');
            $table->string('event_type', 100)->comment('イベントタイプ');
            $table->longText('payload')->comment('受信ペイロード');
            $table->boolean('processed')->default(false)->comment('処理済みフラグ');
            $table->timestamp('processed_at')->nullable()->comment('処理日時');
            $table->text('error_message')->nullable()->comment('エラーメッセージ');
            $table->timestamps();

            $table->index('tenant_id', 'webhook_logs_tenant_id_index');
            $table->index('processed', 'webhook_logs_processed_index');
            $table->index('created_at', 'webhook_logs_created_at_index');

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('fincode_cards');
        Schema::dropIfExists('fincode_customers');
        Schema::dropIfExists('payments');
    }
};
