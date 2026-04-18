<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ReorderService;
use Illuminate\Http\JsonResponse;

class ReorderController extends Controller
{
    public function __construct(private ReorderService $reorderService) {}

    // 注文履歴から再注文する
    public function __invoke(Order $order): JsonResponse
    {
        $this->authorize('reorder', $order);

        $result = $this->reorderService->reorder(request()->user(), $order);

        return response()->json(['data' => $result]);
    }
}
