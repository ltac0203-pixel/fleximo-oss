<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderPauseController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService
    ) {}

    // 注文受付の一時停止/再開をトグルする
    public function toggle(Request $request): JsonResponse
    {
        Gate::authorize('tenant.manage');
        $tenant = $request->user()->getTenant();
        $tenant = $this->tenantService->toggleOrderPause($tenant);

        return response()->json([
            'data' => [
                'is_order_paused' => $tenant->is_order_paused,
                'order_paused_at' => $tenant->order_paused_at?->toIso8601String(),
            ],
            'message' => $tenant->is_order_paused
                ? '注文受付を一時停止しました。'
                : '注文受付を再開しました。',
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        Gate::authorize('tenant.manage');
        $tenant = $request->user()->getTenant();

        return response()->json([
            'data' => [
                'is_order_paused' => $tenant->is_order_paused,
                'order_paused_at' => $tenant->order_paused_at?->toIso8601String(),
            ],
        ]);
    }
}
