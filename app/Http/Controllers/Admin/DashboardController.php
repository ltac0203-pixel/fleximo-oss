<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminRevenueDashboardRequest;
use App\Services\PlatformRevenueDashboardService;
use App\Services\TenantApplicationService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TenantApplicationService $applicationService,
        private readonly PlatformRevenueDashboardService $platformRevenueDashboardService,
    ) {}

    // 管理者ダッシュボードを表示する
    public function index(AdminRevenueDashboardRequest $request): Response
    {
        Gate::authorize('admin.access');

        $stats = $this->applicationService->getDashboardStats();
        $startDate = $request->getStartDate();
        $endDate = $request->getEndDate();
        $rankingLimit = $request->getRankingLimit();
        $revenueDashboard = $this->platformRevenueDashboardService->buildDashboard(
            $startDate,
            $endDate,
            $rankingLimit
        );

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'revenueDashboard' => $revenueDashboard,
            'revenueFilters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'ranking_limit' => $rankingLimit,
            ],
        ]);
    }
}
