<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardSummaryResource;
use App\Http\Resources\SalesDataResource;
use App\Http\Resources\TenantApplicationResource;
use App\Http\Resources\TenantDetailResource;
use App\Services\TenantApplication\TenantApplicationLookupService;
use App\Services\TenantDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// テナントダッシュボードコントローラー
class TenantDashboardController extends Controller
{
    public function __construct(
        private readonly TenantDashboardService $dashboardService,
        private readonly TenantApplicationLookupService $applicationService
    ) {}

    public function index(Request $request): Response
    {
        $tenant = $request->user()->getTenant();

        if (! $tenant->isApproved()) {
            $user = $request->user();
            $application = $this->applicationService->findForTenantOrUser(
                $tenant->id, $user->id
            );

            return Inertia::render('Tenant/Dashboard/Pending', [
                'tenant' => new TenantDetailResource($tenant),
                'application' => $application ? new TenantApplicationResource($application) : null,
            ]);
        }

        $today = Carbon::today();
        $summaryData = $this->dashboardService->getSummary($tenant->id, $today);
        $recentSalesData = $this->dashboardService->getRecentWeekSalesData($tenant->id);

        return Inertia::render('Tenant/Dashboard/Index', [
            'tenant' => new TenantDetailResource($tenant),
            'summary' => (new DashboardSummaryResource($summaryData))->resolve(),
            'recentSales' => SalesDataResource::collection($recentSalesData)->resolve(),
        ]);
    }
}
