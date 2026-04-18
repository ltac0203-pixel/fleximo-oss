<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // audit_logs テーブル
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('実行ユーザー');
            $table->unsignedBigInteger('tenant_id')->nullable()->comment('対象テナント');
            $table->string('action', 100)->comment('アクション名');
            $table->string('auditable_type')->nullable()->comment('対象モデルクラス');
            $table->unsignedBigInteger('auditable_id')->nullable()->comment('対象モデルID');
            $table->longText('old_values')->nullable()->comment('変更前の値');
            $table->longText('new_values')->nullable()->comment('変更後の値');
            $table->longText('metadata')->nullable()->comment('追加メタデータ');
            $table->string('ip_address', 45)->nullable()->comment('IPアドレス');
            $table->text('user_agent')->nullable()->comment('ユーザーエージェント');
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id', 'audit_logs_user_id_index');
            $table->index('tenant_id', 'audit_logs_tenant_id_index');
            $table->index('action', 'audit_logs_action_index');
            $table->index(['auditable_type', 'auditable_id'], 'audit_logs_auditable_index');
            $table->index('created_at', 'audit_logs_created_at_index');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });

        // idempotency_keys テーブル
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('key', 36)->comment('Idempotency key (UUIDv4)');
            $table->string('route_name', 100)->comment('Route name for scoping');
            $table->string('request_method', 10)->comment('HTTP method');
            $table->string('request_hash', 64)->comment('SHA-256 hash of request');
            $table->longText('response_body')->nullable()->comment('Cached response body');
            $table->unsignedSmallInteger('response_status')->nullable()->comment('Cached response status');
            $table->timestamp('expires_at')->comment('Idempotency expiration time');
            $table->timestamps();

            $table->unique(['user_id', 'key', 'route_name'], 'idempotency_keys_user_id_key_route_name_unique');
            $table->index(['key', 'route_name'], 'idempotency_keys_key_route_name_index');
            $table->index('expires_at', 'idempotency_keys_expires_at_index');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // analytics_cache テーブル
        Schema::create('analytics_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->comment('テナントID（NULLはプラットフォーム全体）');
            $table->string('metric_type', 50)->comment('メトリクスタイプ');
            $table->date('date')->comment('集計日');
            $table->longText('data')->comment('集計データ');
            $table->timestamps();

            $table->unique(['tenant_id', 'metric_type', 'date'], 'analytics_cache_unique');
            $table->index(['tenant_id', 'metric_type'], 'analytics_cache_tenant_metric_index');
            $table->index('date', 'analytics_cache_date_index');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // hourly_order_stats テーブル
        Schema::create('hourly_order_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->date('date')->comment('集計日');
            $table->unsignedTinyInteger('hour')->comment('時間（0-23）');
            $table->unsignedInteger('order_count')->default(0)->comment('注文数');
            $table->decimal('total_amount', 12, 2)->default(0)->comment('売上合計');
            $table->timestamps();

            $table->unique(['tenant_id', 'date', 'hour'], 'hourly_order_stats_unique');
            $table->index(['tenant_id', 'date'], 'hourly_order_stats_tenant_date_index');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // tenant_applications テーブル
        Schema::create('tenant_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_code', 20)->unique()->comment('申し込み番号（TAPP-XXXXXXXX）');
            $table->string('applicant_name', 100)->comment('申請者名');
            $table->string('applicant_email')->comment('申請者メール');
            $table->string('applicant_phone', 20)->comment('申請者電話番号');
            $table->string('tenant_name', 100)->comment('店舗名');
            $table->string('tenant_address')->nullable()->comment('住所');
            $table->string('business_type', 50)->comment('業種');
            $table->string('status', 20)->default('pending')->comment('ステータス');
            $table->text('rejection_reason')->nullable()->comment('却下理由');
            $table->text('internal_notes')->nullable()->comment('管理者メモ');
            $table->unsignedBigInteger('reviewed_by')->nullable()->comment('審査者');
            $table->timestamp('reviewed_at')->nullable()->comment('審査日時');
            $table->unsignedBigInteger('created_tenant_id')->nullable()->comment('作成されたテナントID');
            $table->unsignedBigInteger('applicant_user_id')->nullable()->comment('申請者ユーザーID');
            $table->timestamps();

            $table->index('status', 'tenant_applications_status_index');
            $table->index('created_at', 'tenant_applications_created_at_index');
            $table->index('reviewed_by', 'tenant_applications_reviewed_by_foreign');
            $table->index('created_tenant_id', 'tenant_applications_created_tenant_id_foreign');
            $table->index('applicant_user_id', 'tenant_applications_applicant_user_id_index');

            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('applicant_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_applications');
        Schema::dropIfExists('hourly_order_stats');
        Schema::dropIfExists('analytics_cache');
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('audit_logs');
    }
};
