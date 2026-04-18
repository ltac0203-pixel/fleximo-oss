<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // nullable で先に追加することで、既存データへの影響を最小化し段階的なマイグレーションを可能にする
        if (! Schema::hasColumn('options', 'tenant_id')) {
            Schema::table('options', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('option_group_id');
            });
        }

        // option は option_group の子レコードのため、tenant_id は親テーブルから一意に決定される
        DB::statement('
            UPDATE options
            INNER JOIN option_groups ON options.option_group_id = option_groups.id
            SET options.tenant_id = option_groups.tenant_id
        ');

        // NOT NULL 制約を適用する前に NULL が残っていないか検証し、マイグレーション失敗を事前に防ぐ
        $nullCount = DB::table('options')->whereNull('tenant_id')->count();
        if ($nullCount > 0) {
            throw new RuntimeException(
                "Migration failed: {$nullCount} options have NULL tenant_id. ".
                'All options must have a valid tenant_id before applying NOT NULL constraint.'
            );
        }

        // option と option_group の tenant_id が一致しない場合、マルチテナント分離が破壊されるため事前検証する
        $mismatchCount = DB::table('options')
            ->join('option_groups', 'options.option_group_id', '=', 'option_groups.id')
            ->whereColumn('options.tenant_id', '!=', 'option_groups.tenant_id')
            ->count();

        if ($mismatchCount > 0) {
            throw new RuntimeException(
                "Migration failed: {$mismatchCount} options have tenant_id mismatch with their option_group. ".
                'Data integrity check failed.'
            );
        }

        // データ整合性の検証が完了してから NOT NULL 制約を適用し、ロールバック可能な段階的アプローチを維持する
        Schema::table('options', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
        });

        // テナント単位の高速クエリと参照整合性のために、インデックスと外部キー制約を最後に追加する
        $indexExists = fn (string $name) => DB::select(
            'SHOW INDEX FROM options WHERE Key_name = ?',
            [$name]
        );

        if (! $indexExists('options_tenant_id_index')) {
            Schema::table('options', function (Blueprint $table) {
                $table->index('tenant_id', 'options_tenant_id_index');
            });
        }
        if (! $indexExists('options_tenant_id_is_active_index')) {
            Schema::table('options', function (Blueprint $table) {
                $table->index(['tenant_id', 'is_active'], 'options_tenant_id_is_active_index');
            });
        }

        $fkExists = DB::select(
            "SELECT * FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_NAME = ? AND TABLE_NAME = 'options' AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            ['options_tenant_id_foreign']
        );
        if (! $fkExists) {
            Schema::table('options', function (Blueprint $table) {
                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::table('options', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex('options_tenant_id_is_active_index');
            $table->dropIndex('options_tenant_id_index');
            $table->dropColumn('tenant_id');
        });
    }
};
