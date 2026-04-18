<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    private const TENANT_LOOKUP_CACHE_TTL_SECONDS = 86400; // 24時間（不変マッピング）

    private const CART_TENANT_CACHE_KEY_PREFIX = 'tenant_context:cart_tenant:';

    private const PAYMENT_TENANT_CACHE_KEY_PREFIX = 'tenant_context:payment_tenant:';

    // 新しいミドルウェアインスタンスを作成する。
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    // 受信したリクエストを処理する。
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // テナント管理者・スタッフ
        if ($user->hasTenantRole()) {
            $tenantId = $user->getTenantId();
            if ($tenantId !== null) {
                $this->tenantContext->setTenant($tenantId);
            }
        }

        // 顧客はアクセス先テナントをコンテキストに設定する
        // ルートパラメータ → リクエストボディの順で解決を試みる
        if ($user->isCustomer()) {
            $tenantId = $this->resolveCustomerTenantId($request);
            if ($tenantId !== null) {
                $this->tenantContext->setTenant($tenantId);
            } else {
                Log::warning('Customer request could not resolve tenant context', [
                    'user_id' => $user->id,
                    'route' => $request->route()?->getName(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]);
            }
        }

        return $next($request);
    }

    // 顧客リクエストからテナントIDを解決する
    // 優先順位:
    // 1. ルートパラメータ {tenant}（Route Model Binding済み）
    // 2. ルートパラメータ {tenant}（数値ID）
    // 3. リクエストボディの tenant_id（カート追加等、アクティブテナントのみ）
    // 4. リクエストボディの cart_id（checkout）
    // 5. リクエストボディの payment_id（finalize/3ds-callback）
    private function resolveCustomerTenantId(Request $request): ?int
    {
        $routeTenant = $request->route('tenant');

        if ($routeTenant instanceof Tenant) {
            return $routeTenant->id;
        }

        if (is_numeric($routeTenant)) {
            return (int) $routeTenant;
        }

        $inputTenantId = $request->input('tenant_id');
        if (is_numeric($inputTenantId)) {
            $tenantId = (int) $inputTenantId;
            $exists = Cache::remember(
                'tenant_context:active_tenant:'.$tenantId,
                self::TENANT_LOOKUP_CACHE_TTL_SECONDS,
                fn () => DB::table('tenants')->where('id', $tenantId)->where('is_active', true)->exists()
            );

            return $exists ? $tenantId : null;
        }

        $userId = $request->user()->id;

        $cartId = $request->input('cart_id');
        if (is_numeric($cartId)) {
            $tenantId = $this->resolveTenantIdFromCartId((int) $cartId, $userId);

            if ($tenantId !== null) {
                return (int) $tenantId;
            }
        }

        $paymentId = $request->input('payment_id');
        if (is_numeric($paymentId)) {
            $tenantId = $this->resolveTenantIdFromPaymentId((int) $paymentId, $userId);

            if ($tenantId !== null) {
                return (int) $tenantId;
            }
        }

        return null;
    }

    // リクエストライフサイクルの最終処理を実行する。
    public function terminate(Request $request, Response $response): void
    {
        $this->tenantContext->clear();
    }

    private function resolveTenantIdFromCartId(int $cartId, int $userId): ?int
    {
        $cacheKey = self::CART_TENANT_CACHE_KEY_PREFIX.$userId.':'.$cartId;

        $tenantId = Cache::remember($cacheKey, self::TENANT_LOOKUP_CACHE_TTL_SECONDS, function () use ($cartId, $userId) {
            return DB::table('carts')
                ->where('id', $cartId)
                ->where('user_id', $userId)
                ->value('tenant_id');
        });

        return is_numeric($tenantId) ? (int) $tenantId : null;
    }

    private function resolveTenantIdFromPaymentId(int $paymentId, int $userId): ?int
    {
        $cacheKey = self::PAYMENT_TENANT_CACHE_KEY_PREFIX.$userId.':'.$paymentId;

        $tenantId = Cache::remember($cacheKey, self::TENANT_LOOKUP_CACHE_TTL_SECONDS, function () use ($paymentId, $userId) {
            return DB::table('payments')
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->where('payments.id', $paymentId)
                ->where('orders.user_id', $userId)
                ->value('payments.tenant_id');
        });

        return is_numeric($tenantId) ? (int) $tenantId : null;
    }
}
