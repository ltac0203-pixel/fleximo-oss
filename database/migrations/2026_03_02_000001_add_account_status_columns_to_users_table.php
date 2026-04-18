<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_status', 20)->default('active')->after('is_active')->comment('アカウントステータス (active/suspended/banned)');
            $table->text('account_status_reason')->nullable()->after('account_status')->comment('ステータス変更理由');
            $table->timestamp('account_status_changed_at')->nullable()->after('account_status_reason')->comment('ステータス変更日時');
            $table->unsignedBigInteger('account_status_changed_by')->nullable()->after('account_status_changed_at')->comment('ステータス変更者');

            $table->foreign('account_status_changed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('account_status', 'users_account_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['account_status_changed_by']);
            $table->dropIndex('users_account_status_index');
            $table->dropColumn([
                'account_status',
                'account_status_reason',
                'account_status_changed_at',
                'account_status_changed_by',
            ]);
        });
    }
};
