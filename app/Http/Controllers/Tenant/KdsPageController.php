<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\KdsOrderResource;
use App\Services\KdsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KdsPageController extends Controller
{
    public function __construct(
        private readonly KdsService $kdsService
    ) {}

    // KDS（キッチンディスプレイシステム）画面を表示する
    // KDS（キッチンディスプレイシステム）画面を表示する
    public function index(Request $request): Response
    {
        $tenant = $request->user()->getTenant();

        $businessDate = Carbon::today();
        $orders = $this->kdsService->getKdsOrders([], $businessDate);

        return Inertia::render('Tenant/Kds/Index', [
            'orders' => KdsOrderResource::collection($orders)->resolve(),
            'businessDate' => $businessDate->toDateString(),
            'serverTime' => Carbon::now()->subSecond()->toIso8601String(),
            'isOrderPaused' => $tenant->is_order_paused,
            'readyAutoCompleteSeconds' => (int) config('kds.ready_auto_complete_seconds', 300),
        ]);
    }
}
