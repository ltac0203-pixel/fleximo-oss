<?php

declare(strict_types=1);

namespace App\Enums;

enum MetricType: string
{
    case DailySales = 'daily_sales';
    case MonthlySales = 'monthly_sales';
    case DailyOrderStats = 'daily_order_stats';
    case HourlyDistribution = 'hourly_distribution';
    case TopMenuItems = 'top_menu_items';

    public function label(): string
    {
        return match ($this) {
            self::DailySales => '日次売上',
            self::MonthlySales => '月次売上',
            self::DailyOrderStats => '日次注文統計',
            self::HourlyDistribution => '時間帯別分布',
            self::TopMenuItems => '人気商品',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
