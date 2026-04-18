import type { TenantPageProps } from "./tenant";

export type SalesPeriod = "daily" | "weekly" | "monthly";

export interface DashboardPeriodData {
    sales: number;
    orders: number;
    average: number;
}

export interface DashboardComparisonChange {
    sales_percent: number | null;
    orders_percent: number | null;
}

export interface DashboardComparison {
    daily_change: DashboardComparisonChange;
    monthly_change: DashboardComparisonChange;
}

export interface DashboardSummary {
    today: DashboardPeriodData;
    yesterday: DashboardPeriodData;
    this_month: DashboardPeriodData;
    last_month: DashboardPeriodData;
    comparison: DashboardComparison;
}

export interface SalesData {
    date: string;
    sales: number;
    orders: number;
}

export interface TopItem {
    rank: number;
    menu_item_id: number;
    name: string;
    quantity: number;
    revenue: number;
}

export interface HourlyData {
    hour: number;
    orders: number;
    sales: number;
}

export interface DashboardIndexProps extends TenantPageProps {
    summary: DashboardSummary;
    recentSales: SalesData[];
}

export interface PaymentMethodStatsItem {
    method: string;
    label: string;
    count: number;
    amount: number;
}

export interface PaymentMethodStats {
    methods: PaymentMethodStatsItem[];
    total_count: number;
    total_amount: number;
}

export interface CustomerInsights {
    unique_customers: number;
    new_customers: number;
    repeat_customers: number;
    repeat_rate: number;
}
