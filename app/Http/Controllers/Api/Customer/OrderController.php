<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ListOrdersRequest;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderListResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function index(ListOrdersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $orders = $this->orderService->getUserOrders(
            user: $request->user(),
            status: $request->validated('status'),
            perPage: $request->validated('per_page', 20)
        );

        return response()->json([
            'data' => OrderListResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function show(Order $order): OrderDetailResource
    {
        $this->authorize('view', $order);

        $order = $this->orderService->getOrderWithDetails($order);

        return new OrderDetailResource($order);
    }

    public function status(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json([
            'data' => [
                'id' => $order->id,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'is_terminal' => $order->status->isTerminal(),
                'ready_at' => $order->ready_at?->toIso8601String(),
                'updated_at' => $order->updated_at->toIso8601String(),
            ],
        ]);
    }
}
