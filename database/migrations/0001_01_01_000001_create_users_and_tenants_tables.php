<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // users テーブル
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('氏名');
            $table->string('email')->unique()->comment('メールアドレス');
            $table->timestamp('email_verified_at')->nullable()->comment('メール認証日時');
            $table->string('password')->comment('パスワードハッシュ');
            $table->string('role')->default('customer')->index('users_role_index');
            $table->boolean('is_active')->nullable()->default(true);
            $table->string('phone', 20)->nullable()->comment('電話番号');
            $table->timestamp('last_login_at')->nullable()->comment('最終ログイン日時');
            $table->rememberToken();
            $table->timestamps();
        });

        // tenants テーブル
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('テナント名');
            $table->string('slug', 100)->unique()->comment('URLスラッグ');
            $table->string('email')->comment('連絡先メール');
            $table->string('phone', 20)->nullable()->comment('電話番号');
            $table->string('address')->nullable()->comment('住所');
            $table->boolean('is_active')->default(true)->index('tenants_is_active_index')->comment('有効フラグ');
            $table->boolean('is_approved')->default(false)->comment('テナント承認フラグ');
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending', 'rejected'])->nullable()->default('active');
            $table->string('fincode_shop_id')->nullable()->index('tenants_fincode_shop_id_index')->comment('fincode テナントショップID（マルチテナント決済用）');
            $table->timestamps();

            $table->index('status', 'idx_tenants_status');
        });

        // tenant_users テーブル
        Schema::create('tenant_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['admin', 'staff'])->default('staff')->comment('テナント内ロール');
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id'], 'tenant_users_tenant_user_unique');
            $table->index('user_id', 'tenant_users_user_id_foreign');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // tenant_business_hours テーブル
        Schema::create('tenant_business_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedTinyInteger('weekday')->comment('曜日 (0=日..6=土)');
            $table->time('open_time')->comment('開店時間');
            $table->time('close_time')->comment('閉店時間');
            $table->unsignedInteger('sort_order')->default(0)->comment('表示順');
            $table->timestamps();

            $table->index(['tenant_id', 'weekday', 'sort_order'], 'tenant_business_hours_weekday_sort_index');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_business_hours');
        Schema::dropIfExists('tenant_users');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('users');
    }
};
