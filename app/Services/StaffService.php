<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Staff\CreateStaffData;
use App\DTOs\Staff\UpdateStaffData;
use App\Enums\AuditAction;
use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StaffService
{
    public function __construct(
        private TenantUserService $tenantUserService
    ) {}

    public function getStaffList(Tenant $tenant): Collection
    {
        return User::query()
            ->join('tenant_users', 'users.id', '=', 'tenant_users.user_id')
            ->where('tenant_users.tenant_id', $tenant->id)
            ->where('users.is_active', true)
            ->select('users.*', 'tenant_users.role as tenant_role')
            ->get();
    }

    // スタッフ管理ページ用の一覧を取得する（全スタッフ、データ整形済み）
    public function getStaffListForPage(Tenant $tenant): Collection
    {
        return $tenant->allStaff()
            ->join('tenant_users as tu', function ($join) use ($tenant) {
                $join->on('users.id', '=', 'tu.user_id')
                    ->where('tu.tenant_id', $tenant->id);
            })
            ->select('users.*', 'tu.role as tenant_role')
            ->orderBy('users.created_at', 'desc')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'role' => $user->tenant_role,
                'created_at' => $user->created_at->toISOString(),
            ]);
    }

    // スタッフを作成する
    public function createStaff(Tenant $tenant, CreateStaffData $data): User
    {
        return DB::transaction(function () use ($tenant, $data) {
            // 1. まずプラットフォーム共通のUserレコードを作成する（認証基盤はUser単位のため）
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,  // Userモデルのcastsで自動ハッシュ化される
                'phone' => $data->phone,
            ]);
            $user->is_active = true;
            $user->role = UserRole::TenantStaff;
            $user->save();

            // 2. テナントとの紐付けレコード(tenant_users)を作成し、テナント内での権限を付与する
            $this->tenantUserService->assignUserToTenant(
                $user,
                $tenant,
                TenantUserRole::Staff
            );

            // 3. 誰がいつスタッフを作成したかを追跡するため、監査ログを残す
            AuditLogger::log(action: AuditAction::StaffCreated, target: $user, tenantId: $tenant->id);

            return $user;
        });
    }

    // スタッフ情報を更新する
    public function updateStaff(User $staff, UpdateStaffData $data): User
    {
        return DB::transaction(function () use ($staff, $data) {
            // 監査ログで変更前後の差分を記録するため、更新前の値をスナップショットしておく
            $oldAttributes = $staff->only(['name', 'email', 'phone', 'is_active']);

            // 部分更新に対応するため、リクエストに含まれるフィールドのみ更新対象にする
            $updateData = $data->toArray();

            // is_active は$fillable外のため、DTOから取り出して直接属性代入する
            if (array_key_exists('is_active', $updateData)) {
                $staff->is_active = $updateData['is_active'];
                unset($updateData['is_active']);
            }

            // $fillable対象のフィールドはmass assignmentで更新する
            if (! empty($updateData)) {
                $staff->fill($updateData);
            }
            $staff->save();

            // 監査ログにold/newの差分を記録するため、更新後の値も取得する
            $newAttributes = $staff->only(['name', 'email', 'phone', 'is_active']);

            // 変更内容を監査ログに記録し、不正な変更を事後追跡できるようにする
            AuditLogger::log(
                action: AuditAction::StaffUpdated,
                target: $staff,
                changes: [
                    'old' => $oldAttributes,
                    'new' => $newAttributes,
                ],
                tenantId: $staff->getTenantId()
            );

            return $staff;
        });
    }

    // スタッフを削除する（論理削除）
    public function deleteStaff(User $staff): void
    {
        DB::transaction(function () use ($staff) {
            $tenantId = $staff->getTenantId();

            // 1. 物理削除ではなく論理削除にすることで、過去の注文やログとの参照整合性を維持する
            $staff->is_active = false;
            $staff->save();

            // 2. テナントへのアクセス権を即座に剥奪するため、紐付けレコードは物理削除する
            $staff->tenantUser()->delete();

            // 3. 既存セッションからのアクセスを即座に無効化するため、全トークンを削除する
            $staff->tokens()->delete();

            // 4. スタッフ削除の操作履歴を残し、事後の問い合わせに対応できるようにする
            AuditLogger::log(action: AuditAction::StaffDeleted, target: $staff, tenantId: $tenantId);
        });
    }
}
