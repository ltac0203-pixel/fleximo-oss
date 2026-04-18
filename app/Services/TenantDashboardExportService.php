<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\SalesPeriod;
use App\Models\Order;
use App\Models\Scopes\TenantScope;
use App\Services\Concerns\SanitizesCsvExport;
use Carbon\Carbon;

class TenantDashboardExportService
{
    use SanitizesCsvExport;

    public function __construct(
        private readonly TenantDashboardService $dashboardService
    ) {}

    public function buildExportData(int $tenantId, string $tenantName, Carbon $startDate, Carbon $endDate): array
    {
        $dailySales = $this->dashboardService->getSalesData(
            $tenantId,
            SalesPeriod::Daily,
            $startDate->copy(),
            $endDate->copy(),
        );
        $paymentStats = $this->dashboardService->getPaymentMethodStats(
            $tenantId,
            $startDate->copy(),
            $endDate->copy(),
        );

        $dailyBreakdown = $this->buildDailyBreakdown($dailySales);
        $orderDetails = $this->buildOrderDetails($tenantId, $startDate, $endDate);

        $totalSales = array_sum(array_map(static fn (array $item): int => $item['sales'], $dailyBreakdown));
        $totalOrders = array_sum(array_map(static fn (array $item): int => $item['orders'], $dailyBreakdown));

        return [
            'tenant_id' => $tenantId,
            'tenant_name' => $tenantName,
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'summary' => [
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
                'average_order_value' => $totalOrders > 0 ? (int) round($totalSales / $totalOrders) : 0,
            ],
            'daily_breakdown' => $dailyBreakdown,
            'payment_methods' => $paymentStats['methods'],
            'payment_total_count' => (int) $paymentStats['total_count'],
            'payment_total_amount' => (int) $paymentStats['total_amount'],
            'order_details' => $orderDetails,
            'note' => '税額列は出力していません。税額は会計システム側で別途計算してください。',
        ];
    }

    // エクスポートデータをCSV形式でphp://outputにストリーミング出力する
    public function streamCsv(array $exportData): void
    {
        $output = fopen('php://output', 'wb');
        if ($output === false) {
            return;
        }

        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, ['metadata']);
        fputcsv($output, $this->sanitizeCsvRow(['tenant_id', $exportData['tenant_id']]));
        fputcsv($output, $this->sanitizeCsvRow(['tenant_name', $exportData['tenant_name']]));
        fputcsv($output, $this->sanitizeCsvRow(['period_start', $exportData['period']['start_date']]));
        fputcsv($output, $this->sanitizeCsvRow(['period_end', $exportData['period']['end_date']]));
        fputcsv($output, $this->sanitizeCsvRow(['generated_at', $exportData['generated_at']]));
        fputcsv($output, []);

        fputcsv($output, ['summary']);
        fputcsv($output, ['total_sales', 'total_orders', 'average_order_value']);
        fputcsv($output, [
            $exportData['summary']['total_sales'],
            $exportData['summary']['total_orders'],
            $exportData['summary']['average_order_value'],
        ]);
        fputcsv($output, []);

        fputcsv($output, ['daily_breakdown']);
        fputcsv($output, ['date', 'sales', 'orders', 'average']);
        foreach ($exportData['daily_breakdown'] as $day) {
            fputcsv($output, $this->sanitizeCsvRow([
                $day['date'],
                $day['sales'],
                $day['orders'],
                $day['average'],
            ]));
        }
        fputcsv($output, []);

        fputcsv($output, ['payment_methods']);
        fputcsv($output, ['method', 'label', 'count', 'amount']);
        foreach ($exportData['payment_methods'] as $method) {
            fputcsv($output, $this->sanitizeCsvRow([
                $method['method'],
                $method['label'],
                $method['count'],
                $method['amount'],
            ]));
        }
        fputcsv($output, [
            'TOTAL',
            '',
            $exportData['payment_total_count'],
            $exportData['payment_total_amount'],
        ]);
        fputcsv($output, []);

        fputcsv($output, ['order_details']);
        fputcsv($output, [
            'business_date',
            'order_code',
            'order_status',
            'order_status_label',
            'payment_method',
            'payment_method_label',
            'total_amount',
            'created_at',
        ]);
        foreach ($exportData['order_details'] as $detail) {
            fputcsv($output, $this->sanitizeCsvRow([
                $detail['business_date'],
                $detail['order_code'],
                $detail['order_status'],
                $detail['order_status_label'],
                $detail['payment_method'],
                $detail['payment_method_label'],
                $detail['total_amount'],
                $detail['created_at'],
            ]));
        }
        fputcsv($output, []);
        fputcsv($output, $this->sanitizeCsvRow(['note', $exportData['note']]));

        fclose($output);
    }

    // @param  array<int, array<string, mixed>>  $dailySales
    // @return array<int, array<string, int|string>>
    private function buildDailyBreakdown(array $dailySales): array
    {
        $result = [];

        foreach ($dailySales as $day) {
            $sales = (int) ($day['total_sales'] ?? 0);
            $orders = (int) ($day['order_count'] ?? 0);

            $result[] = [
                'date' => (string) ($day['date'] ?? ''),
                'sales' => $sales,
                'orders' => $orders,
                'average' => $orders > 0 ? (int) round($sales / $orders) : 0,
            ];
        }

        return $result;
    }

    // @return iterable<int, array<string, int|string>>
    private function buildOrderDetails(int $tenantId, Carbon $startDate, Carbon $endDate): iterable
    {
        return Order::withoutGlobalScope(TenantScope::class)
            ->leftJoin('payments', 'orders.payment_id', '=', 'payments.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.business_date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ])
            ->whereIn('orders.status', OrderStatus::salesStatusValues())
            ->orderBy('orders.business_date')
            ->orderBy('orders.created_at')
            ->select([
                'orders.business_date',
                'orders.order_code',
                'orders.status',
                'orders.total_amount',
                'orders.created_at',
                'payments.method as payment_method',
            ])
            ->lazy()
            ->map(static function (object $order): array {
                $statusValue = $order->status instanceof OrderStatus
                    ? $order->status->value
                    : (string) $order->status;
                $status = $order->status instanceof OrderStatus
                    ? $order->status
                    : OrderStatus::tryFrom($statusValue);
                $paymentMethod = PaymentMethod::tryFrom((string) $order->payment_method);

                return [
                    'business_date' => Carbon::parse((string) $order->business_date)->format('Y-m-d'),
                    'order_code' => (string) $order->order_code,
                    'order_status' => $statusValue,
                    'order_status_label' => $status?->label() ?? $statusValue,
                    'payment_method' => $paymentMethod?->value ?? '-',
                    'payment_method_label' => $paymentMethod?->label() ?? '-',
                    'total_amount' => (int) $order->total_amount,
                    'created_at' => Carbon::parse((string) $order->created_at)->format('Y-m-d H:i:s'),
                ];
            });
    }
}
