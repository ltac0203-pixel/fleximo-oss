<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\AccountStatus;
use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\StringHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerManagementService
{
    public function getCustomers(
        ?AccountStatus $status = null,
        ?string $search = null,
        int $perPage = 20,
        ?string $sortBy = 'created_at',
        string $sortDir = 'desc'
    ): LengthAwarePaginator {
        return User::query()
            ->where('role', UserRole::Customer->value)
            ->when($status, fn ($q) => $q->where('account_status', $status->value))
            ->when($search, function ($q, $s) {
                $escaped = StringHelper::escapeLike($s);
                $q->where(function ($q) use ($escaped) {
                    $q->where('name', 'like', "%{$escaped}%")
                        ->orWhere('email', 'like', "%{$escaped}%");
                });
            })
            ->withCount(['orders' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)])
            ->orderBy($sortBy ?? 'created_at', $sortDir)
            ->paginate($perPage);
    }

    // 顧客詳細をサマリー統計付きで取得する
    public function getCustomerDetail(User $customer): User
    {
        $customer->loadCount(['favoriteTenants']);

        $orderStats = Order::withoutGlobalScope(TenantScope::class)
            ->where('user_id', $customer->id)
            ->selectRaw('COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as total_spent')
            ->first();

        $customer->setAttribute('total_orders', (int) $orderStats->total_orders);
        $customer->setAttribute('total_spent', (int) $orderStats->total_spent);

        $customer->load('accountStatusChangedBy');

        return $customer;
    }

    public function getCustomerOrders(
        User $customer,
        int $perPage = 20,
        ?int $tenantId = null,
        ?string $orderStatus = null
    ): LengthAwarePaginator {
        return Order::withoutGlobalScope(TenantScope::class)
            ->where('user_id', $customer->id)
            ->when($tenantId, fn ($q, $tid) => $q->where('tenant_id', $tid))
            ->when($orderStatus, fn ($q, $s) => $q->where('status', $s))
            ->with(['tenant:id,name', 'items', 'payment'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    // 顧客を一時停止する
    public function suspendCustomer(User $customer, string $reason, User $admin): User
    {
        return DB::transaction(function () use ($customer, $reason, $admin) {
            $customer->applyAccountStatus(AccountStatus::Suspended, $reason, $admin->id, false);

            $customer->tokens()->delete();

            AuditLogger::log(
                action: AuditAction::CustomerSuspended,
                target: $customer,
                changes: [
                    'new' => ['account_status' => AccountStatus::Suspended->value],
                    'metadata' => ['reason' => $reason],
                ],
            );

            return $customer->refresh();
        });
    }

    // 顧客をBANする
    public function banCustomer(User $customer, string $reason, User $admin): User
    {
        return DB::transaction(function () use ($customer, $reason, $admin) {
            $customer->applyAccountStatus(AccountStatus::Banned, $reason, $admin->id, false);

            $customer->tokens()->delete();

            AuditLogger::log(
                action: AuditAction::CustomerBanned,
                target: $customer,
                changes: [
                    'new' => ['account_status' => AccountStatus::Banned->value],
                    'metadata' => ['reason' => $reason],
                ],
            );

            return $customer->refresh();
        });
    }

    // 顧客を再有効化する
    public function reactivateCustomer(User $customer, User $admin): User
    {
        return DB::transaction(function () use ($customer, $admin) {
            $oldStatus = $customer->account_status->value;

            $customer->applyAccountStatus(AccountStatus::Active, null, $admin->id, true);

            AuditLogger::log(
                action: AuditAction::CustomerReactivated,
                target: $customer,
                changes: [
                    'old' => ['account_status' => $oldStatus],
                    'new' => ['account_status' => AccountStatus::Active->value],
                ],
            );

            return $customer->refresh();
        });
    }
}
