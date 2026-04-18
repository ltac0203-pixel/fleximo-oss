<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\AuditAction;
use App\Models\Order;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Concerns\SanitizesCsvExport;

class CustomerDataExportService
{
    use SanitizesCsvExport;

    // 機密情報として除外するフィールド
    private const EXCLUDED_FIELDS = [
        'password',
        'remember_token',
        'fincode_customer_id',
        'fincode_card_id',
        'fincode_id',
        'fincode_access_id',
    ];

    // エクスポートデータを構築する
    public function buildExportData(User $customer): array
    {
        $profile = collect($customer->toArray())
            ->except(self::EXCLUDED_FIELDS)
            ->toArray();

        $orders = Order::withoutGlobalScope(TenantScope::class)
            ->where('user_id', $customer->id)
            ->select(['id', 'user_id', 'tenant_id', 'order_code', 'status', 'total_amount', 'created_at'])
            ->with(['tenant:id,name', 'payment:id,order_id,method,status'])
            ->orderByDesc('created_at')
            ->lazy()
            ->map(fn ($order) => [
                'order_code' => $order->order_code,
                'tenant_name' => $order->tenant?->name,
                'status' => $order->status->value,
                'total_amount' => $order->total_amount,
                'payment_method' => $order->payment?->method?->value ?? null,
                'payment_status' => $order->payment?->status ?? null,
                'created_at' => $order->created_at?->toISOString(),
            ])
            ->all();

        $favorites = $customer->favoriteTenants()
            ->get(['tenants.id', 'tenants.name'])
            ->map(fn ($tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ])
            ->toArray();

        AuditLogger::log(
            action: AuditAction::CustomerDataExported,
            target: $customer,
            changes: [
                'metadata' => [
                    'exported_sections' => ['profile', 'orders', 'favorites'],
                ],
            ],
        );

        return [
            'exported_at' => now()->toISOString(),
            'profile' => $profile,
            'orders' => $orders,
            'favorites' => $favorites,
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

        // メタデータ
        fputcsv($output, ['exported_at', $exportData['exported_at']]);
        fputcsv($output, []);

        // プロフィール
        fputcsv($output, ['profile']);
        foreach ($exportData['profile'] as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            fputcsv($output, $this->sanitizeCsvRow([(string) $key, (string) ($value ?? '')]));
        }
        fputcsv($output, []);

        // 注文履歴
        fputcsv($output, ['orders']);
        if (count($exportData['orders']) > 0) {
            fputcsv($output, ['order_code', 'tenant_name', 'status', 'total_amount', 'payment_method', 'payment_status', 'created_at']);
            foreach ($exportData['orders'] as $order) {
                fputcsv($output, $this->sanitizeCsvRow([
                    $order['order_code'],
                    $order['tenant_name'] ?? '',
                    $order['status'],
                    $order['total_amount'],
                    $order['payment_method'] ?? '',
                    $order['payment_status'] ?? '',
                    $order['created_at'] ?? '',
                ]));
            }
        }
        fputcsv($output, []);

        // お気に入りテナント
        fputcsv($output, ['favorites']);
        if (count($exportData['favorites']) > 0) {
            fputcsv($output, ['id', 'name']);
            foreach ($exportData['favorites'] as $fav) {
                fputcsv($output, $this->sanitizeCsvRow([$fav['id'], $fav['name']]));
            }
        }

        fclose($output);
    }
}
