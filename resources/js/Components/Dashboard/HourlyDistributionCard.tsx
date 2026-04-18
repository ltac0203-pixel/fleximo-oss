import { useId } from "react";
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from "recharts";
import type { TooltipContentProps, TooltipPayloadEntry } from "recharts";
import { useHourlyDistributionData } from "@/Hooks/useHourlyDistributionData";
import { formatCurrency } from "@/Utils/formatPrice";
import GeoSurface from "@/Components/GeoSurface";
import DashboardAsyncState from "./DashboardAsyncState";
import DashboardChartErrorBoundary from "./DashboardChartErrorBoundary";
import useKeyboardChartNavigation from "./useKeyboardChartNavigation";

type CustomTooltipProps = Partial<Pick<TooltipContentProps<number, string>, "active" | "payload" | "label">>;

function parseNumericTooltipValue(value: unknown): number | null {
    if (typeof value === "number") {
        return value;
    }

    if (typeof value === "string") {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : null;
    }

    return null;
}

function getMetricLabel(dataKey: string): string {
    return dataKey === "sales" ? "売上" : "注文数";
}

function CustomTooltip({ active, payload, label }: CustomTooltipProps) {
    if (!active || !payload || !payload.length) return null;

    return (
        <div className="bg-white border border-edge p-3">
            <p className="text-sm font-medium text-ink mb-1">{label}時</p>
            {payload.map((rawEntry) => {
                const entry = rawEntry as TooltipPayloadEntry<number, string>;
                const dataKey = typeof entry.dataKey === "string" ? entry.dataKey : "";
                const value = parseNumericTooltipValue(entry.value);
                if (!dataKey || value === null) {
                    return null;
                }

                return (
                    <p key={dataKey} className="text-sm text-ink-light">
                        <span className="font-medium text-ink">[{getMetricLabel(dataKey)}]</span>:{" "}
                        {dataKey === "sales" ? formatCurrency(value) : `${value}件`}
                    </p>
                );
            })}
        </div>
    );
}

export default function HourlyDistributionCard() {
    const { selectedDate, maxDate, chartData, loading, fetchError, onDateChange } = useHourlyDistributionData();
    const chartDescriptionId = useId();
    const barPatternId = `hourly-bar-pattern-${useId().replace(/:/g, "-")}`;
    const { activeItem, isTooltipVisible, onFocus, onBlur, onKeyDown } = useKeyboardChartNavigation(chartData);

    return (
        <GeoSurface topAccent elevated className="p-4">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-medium text-ink">時間帯別注文</h3>
                <input
                    type="date"
                    value={selectedDate}
                    onChange={(event) => onDateChange(event.target.value)}
                    max={maxDate}
                    className="border border-edge-strong bg-white px-3 py-1 text-sm text-ink-light focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    aria-label="集計日"
                />
            </div>

            <DashboardAsyncState
                loading={loading}
                fetchError={fetchError}
                isEmpty={chartData.length === 0}
                heightClassName="h-64"
            >
                <DashboardChartErrorBoundary heightClassName="h-64">
                    <p id={chartDescriptionId} className="sr-only">
                        チャートにフォーカスした後、左右矢印キーで時間帯ごとの詳細を確認できます。
                    </p>
                    <div
                        className="h-64 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        role="group"
                        tabIndex={0}
                        aria-label="時間帯別注文チャート"
                        aria-describedby={chartDescriptionId}
                        onFocus={onFocus}
                        onBlur={onBlur}
                        onKeyDown={onKeyDown}
                    >
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={chartData} accessibilityLayer>
                                <defs>
                                    <pattern id={barPatternId} width="8" height="8" patternUnits="userSpaceOnUse">
                                        <rect width="8" height="8" fill="#0ea5e9" />
                                        <path d="M0 0L8 8M8 0L0 8" stroke="#ffffff" strokeWidth="1" opacity="0.45" />
                                    </pattern>
                                </defs>
                                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                <XAxis
                                    dataKey="hourLabel"
                                    tick={{ fontSize: 11, fill: "#6b7280" }}
                                    tickLine={false}
                                    interval={1}
                                />
                                <YAxis tick={{ fontSize: 12, fill: "#6b7280" }} tickLine={false} />
                                <Tooltip content={<CustomTooltip />} />
                                <Bar
                                    dataKey="orders"
                                    name="注文数"
                                    fill={`url(#${barPatternId})`}
                                    stroke="#0284c7"
                                    strokeWidth={1}
                                    radius={[2, 2, 0, 0]}
                                />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                    {isTooltipVisible && activeItem && (
                        <div
                            role="status"
                            aria-live="polite"
                            className="mt-3 rounded-md border border-edge bg-surface p-3 text-sm text-ink-light"
                        >
                            <p className="font-medium text-ink mb-1">{activeItem.hourLabel}時台</p>
                            <p>[注文数] {activeItem.orders}件</p>
                            <p>[売上] {formatCurrency(activeItem.sales)}</p>
                        </div>
                    )}
                </DashboardChartErrorBoundary>
            </DashboardAsyncState>
        </GeoSurface>
    );
}
