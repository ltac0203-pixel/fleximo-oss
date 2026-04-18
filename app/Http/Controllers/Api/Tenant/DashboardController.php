<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\DashboardCustomerInsightsRequest;
use App\Http\Requests\Tenant\DashboardExportRequest;
use App\Http\Requests\Tenant\DashboardHourlyRequest;
use App\Http\Requests\Tenant\DashboardPaymentMethodsRequest;
use App\Http\Requests\Tenant\DashboardSalesRequest;
use App\Http\Requests\Tenant\DashboardSummaryRequest;
use App\Http\Requests\Tenant\DashboardTopItemsRequest;
use App\Http\Resources\DashboardSummaryResource;
use App\Http\Resources\SalesDataResource;
use App\Http\Resources\TopItemResource;
use App\Services\TenantDashboardExportService;
use App\Services\TenantDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TenantDashboardService $dashboardService,
        private readonly TenantDashboardExportService $dashboardExportService,
    ) {}

    public function summary(DashboardSummaryRequest $request): JsonResponse
    {
        Gate::authorize('dashboard.view');

        $tenantId = $request->user()->getTenantId();
        $date = $request->getDate();

        $summary = $this->dashboardService->getSummary($tenantId, $date);

        return response()->json([
            'data' => new DashboardSummaryResource($summary),
        ]);
    }

    public function sales(DashboardSalesRequest $request): JsonResponse
    {
        Gate::authorize('dashboard.view');

        $tenantId = $request->user()->getTenantId();
        $period = $request->getPeriod();
        $startDate = $request->getStartDate();
        $endDate = $request->getEndDate();

        $salesData = $this->dashboardService->getSalesData(
            $tenantId,
            $period,
            $startDate,
            $endDate
        );

        return response()->json([
            'data' => SalesDataResource::collection($salesData),
        ]);
    }

    public function topItems(DashboardTopItemsRequest $request): JsonResponse
    {
        Gate::authorize('dashboard.view');

        $tenantId = $request->user()->getTenantId();
        $period = $request->getPeriod();
        $limit = $request->getLimit();

        $topItems = $this->dashboardService->getTopItems(
            $tenantId,
            $period,
            $limit
        );

        return response()->json([
            'data' => TopItemResource::collection($topItems),
        ]);
    }

    public function hourly(DashboardHourlyRequest $request): JsonResponse
    {
        Gate::authorize('dashboard.view');

        $tenantId = $request->user()->getTenantId();
        $date = $request->getDate();

        $hourlyData = $this->dashboardService->getHourlyDistribution(
            $tenantId,
            $date
        );

        return response()->json([
            'data' => $hourlyData,
        ]);
    }

    public function paymentMethods(DashboardPaymentMethodsRequest $request): JsonResponse
    {
        Gate::authorize('dashboard.view');

        $tenantId = $request->user()->getTenantId();
        $startDate = $request->getStartDate();
        $endDate = $request->getEndDate();

        $stats = $this->dashboardService->getPaymentMethodStats(
            $tenantId,
            $startDate,
            $endDate
        );

        return response()->json([
            'data' => $stats,
        ]);
    }

    public function customerInsights(DashboardCustomerInsightsRequest $request): JsonResponse
    {
        Gate::authorize('dashboard.view');

        $tenantId = $request->user()->getTenantId();
        $startDate = $request->getStartDate();
        $endDate = $request->getEndDate();

        $insights = $this->dashboardService->getCustomerInsights(
            $tenantId,
            $startDate,
            $endDate
        );

        return response()->json([
            'data' => $insights,
        ]);
    }

    public function exportCsv(DashboardExportRequest $request): StreamedResponse
    {
        Gate::authorize('dashboard.exportCsv');

        $user = $request->user();
        $tenantId = $user->getTenantId();
        assert($tenantId !== null);

        $tenantName = $user->getTenant()?->name ?? 'Unknown Tenant';
        $startDate = $request->getStartDate();
        $endDate = $request->getEndDate();

        $exportData = $this->dashboardExportService->buildExportData(
            $tenantId,
            $tenantName,
            $startDate,
            $endDate,
        );

        $fileName = sprintf(
            'sales_export_%d_%s_%s.csv',
            $tenantId,
            $startDate->format('Ymd'),
            $endDate->format('Ymd')
        );

        return response()->streamDownload(
            fn () => $this->dashboardExportService->streamCsv($exportData),
            $fileName,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }
}
