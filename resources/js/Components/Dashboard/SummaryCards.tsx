import { DashboardSummary } from "@/types";
import { formatNumber, formatPrice } from "@/Utils/formatPrice";
import SummaryCard from "./SummaryCard";

interface SummaryCardsProps {
    summary: DashboardSummary;
}

const SUMMARY_SKELETON_KEYS = ["today-sales", "today-orders", "month-sales", "month-orders"] as const;

export default function SummaryCards({ summary }: SummaryCardsProps) {
    if (!summary || !summary.today || !summary.this_month || !summary.comparison) {
        return (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {SUMMARY_SKELETON_KEYS.map((key) => (
                    <div key={key} data-testid="summary-skeleton" className="geo-surface p-4 animate-pulse">
                        <div className="mb-2 h-4 w-24 bg-edge"></div>
                        <div className="mb-1 h-8 w-32 bg-edge"></div>
                        <div className="h-3 w-16 bg-edge"></div>
                    </div>
                ))}
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <SummaryCard
                title="本日の売上"
                value={summary.today.sales}
                subValue={`${formatNumber(summary.today.orders)}件`}
                change={summary.comparison?.daily_change?.sales_percent}
                changeLabel="前日比"
            />
            <SummaryCard
                title="本日の注文数"
                value={`${formatNumber(summary.today.orders)}件`}
                subValue={`平均 ${formatPrice(summary.today.average)}`}
                change={summary.comparison?.daily_change?.orders_percent}
                changeLabel="前日比"
            />
            <SummaryCard
                title="今月の売上"
                value={summary.this_month.sales}
                subValue={`${formatNumber(summary.this_month.orders)}件`}
                change={summary.comparison?.monthly_change?.sales_percent}
                changeLabel="前月比"
            />
            <SummaryCard
                title="今月の注文数"
                value={`${formatNumber(summary.this_month.orders)}件`}
                subValue={`平均 ${formatPrice(summary.this_month.average)}`}
                change={summary.comparison?.monthly_change?.orders_percent}
                changeLabel="前月比"
            />
        </div>
    );
}
