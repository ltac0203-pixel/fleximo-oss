<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\KdsOrdersRequest;
use App\Http\Requests\Tenant\UpdateOrderStatusRequest;
use App\Http\Resources\KdsOrderResource;
use App\Models\Order;
use App\Services\KdsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class KdsController extends Controller
{
    public function __construct(
        private readonly KdsService $kdsService
    ) {}

    public function index(KdsOrdersRequest $request): JsonResponse
    {
        $this->authorize('viewAnyForTenant', Order::class);

        $statuses = $request->getStatuses();
        $businessDate = $request->validated('business_date')
            ? Carbon::parse($request->validated('business_date'))
            : null;
        $updatedSince = $request->getUpdatedSince();

        $orders = $this->kdsService->getKdsOrders($statuses, $businessDate, $updatedSince);

        return response()->json([
            'data' => KdsOrderResource::collection($orders),
            'meta' => [
                'server_time' => Carbon::now()->subSecond()->toIso8601String(),
            ],
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('viewForTenant', $order);

        $order = $this->kdsService->getOrderWithDetails($order);

        return response()->json([
            'data' => new KdsOrderResource($order),
        ]);
    }

    // 注文ステータスを更新する
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $this->authorize('updateStatus', $order);

        $newStatus = $request->getStatus();

        $order = $this->kdsService->updateOrderStatus($order, $newStatus);

        return response()->json([
            'data' => new KdsOrderResource($order->loadKdsDetails()),
            'message' => "注文を「{$newStatus->label()}」に更新しました。",
        ]);
    }
}
