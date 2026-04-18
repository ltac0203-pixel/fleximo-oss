<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @property array $today
// @property array $yesterday
// @property array $this_month
// @property array $last_month
// @property array $comparison
class DashboardSummaryResource extends JsonResource
{
    // リソースを配列に変換する。
    public function toArray(Request $request): array
    {
        return [
            'today' => [
                'sales' => $this->resource['today']['total_sales'],
                'orders' => $this->resource['today']['order_count'],
                'average' => $this->resource['today']['average_order_value'],
            ],
            'yesterday' => [
                'sales' => $this->resource['yesterday']['total_sales'],
                'orders' => $this->resource['yesterday']['order_count'],
                'average' => $this->resource['yesterday']['average_order_value'],
            ],
            'this_month' => [
                'sales' => $this->resource['this_month']['total_sales'],
                'orders' => $this->resource['this_month']['order_count'],
                'average' => $this->resource['this_month']['average_order_value'],
            ],
            'last_month' => [
                'sales' => $this->resource['last_month']['total_sales'],
                'orders' => $this->resource['last_month']['order_count'],
                'average' => $this->resource['last_month']['average_order_value'],
            ],
            'comparison' => [
                'daily_change' => [
                    'sales_percent' => $this->resource['comparison']['daily_sales_percent'],
                    'orders_percent' => $this->resource['comparison']['daily_orders_percent'],
                ],
                'monthly_change' => [
                    'sales_percent' => $this->resource['comparison']['monthly_sales_percent'],
                    'orders_percent' => $this->resource['comparison']['monthly_orders_percent'],
                ],
            ],
        ];
    }
}
