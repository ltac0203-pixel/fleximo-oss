<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ListOrdersRequest;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderListResource;
use App\Models\Order;
use App\Models\Scopes\TenantScope;
use App\Services\OrderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class OrderPageController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected OrderService $orderService
    ) {}

    public function index(ListOrdersRequest $request): Response
    {
        $this->authorize('viewAny', Order::class);

        $perPage = $request->validated('per_page', 10);

        $orders = $this->orderService->getUserPaidOrders(
            $request->user(),
            (int) $perPage
        );

        return Inertia::render('Customer/Orders/Index', [
            'orders' => OrderListResource::collection($orders)->response()->getData(true),
        ]);
    }

    public function show(int $order): Response
    {
        $orderModel = Order::withoutGlobalScope(TenantScope::class)
            ->findOrFail($order);
        $this->authorize('view', $orderModel);

        $orderWithDetails = $this->orderService->getOrderWithDetails($orderModel);

        return Inertia::render('Customer/Orders/Show', [
            'order' => (new OrderDetailResource($orderWithDetails))->resolve(),
        ]);
    }
}
