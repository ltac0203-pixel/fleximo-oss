import { useId } from "react";
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from "recharts";
import type { TooltipContentProps, TooltipPayloadEntry } from "recharts";
import { useSalesChartData } from "@/Hooks/useSalesChartData";
import { formatCurrency } from "@/Utils/formatPrice";
import { SalesData } from "@/types";
import GeoSurface from "@/Components/GeoSurface";
import DashboardAsyncState from "./DashboardAsyncState";
import DashboardChartErrorBoundary from "./DashboardChartErrorBoundary";
import PeriodSelector from "./PeriodSelector";
import useKeyboardChartNavigation from "./useKeyboardChartNavigation";

/** Y軸の売上表示を千円単位にする除数 */
const CURRENCY_SCALE_DIVISOR = 1000;

interface SalesChartProps {
    initialData: SalesData[];
}

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

function getSeriesStyleLabel(name: string): string {
    return name === "売上" ? "実線" : "破線";
}

function CustomTooltip({ active, payload, label }: CustomTooltipProps) {
    if (!active || !payload || !payload.length) return null;

    return (
        <div className="bg-white border border-edge p-3">
            <p className="text-sm font-medium text-ink mb-1">{label}</p>
            {payload.map((rawEntry) => {
                const entry = rawEntry as TooltipPayloadEntry<number, string>;
                const name = entry.name ?? "";
                const value = parseNumericTooltipValue(entry.value);
                if (!name || value === null) {
                    return null;
                }

                return (
                    <p key={String(name)} className="text-sm text-ink-light">
                        <span className="font-medium text-ink">
                            [{getSeriesStyleLabel(name)}] {name}
                        </span>
                        : {name === "売上" ? formatCurrency(value) : `${value}件`}
                    </p>
                );
            })}
        </div>
    );
}

export default function SalesChart({ initialData }: SalesChartProps) {
    const { period, chartData, loading, fetchError, onPeriodChange } = useSalesChartData(initialData ?? []);
    const chartDescriptionId = useId();
    const { activeItem, isTooltipVisible, onFocus, onBlur, onKeyDown } = useKeyboardChartNavigation(chartData);

    return (
        <GeoSurface topAccent elevated className="p-4">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-medium text-ink">売上推移</h3>
                <PeriodSelector selected={period} onChange={onPeriodChange} />
            </div>

            <DashboardAsyncState
                loading={loading}
                fetchError={fetchError}
                isEmpty={chartData.length === 0}
                heightClassName="h-64"
            >
                <DashboardChartErrorBoundary heightClassName="h-64">
                    <p id={chartDescriptionId} className="sr-only">
                        チャートにフォーカスした後、左右矢印キーで日付ごとの詳細を確認できます。
                    </p>
                    <div
                        className="h-64 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        role="group"
                        tabIndex={0}
                        aria-label="売上推移チャート"
                        aria-describedby={chartDescriptionId}
                        onFocus={onFocus}
                        onBlur={onBlur}
                        onKeyDown={onKeyDown}
                    >
                        <ResponsiveContainer width="100%" height="100%">
                            <LineChart data={chartData} accessibilityLayer>
                                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                <XAxis dataKey="dateLabel" tick={{ fontSize: 12, fill: "#6b7280" }} tickLine={false} />
                                <YAxis
                                    yAxisId="sales"
                                    tick={{ fontSize: 12, fill: "#6b7280" }}
                                    tickLine={false}
                                    tickFormatter={(value) => `¥${(value / CURRENCY_SCALE_DIVISOR).toFixed(0)}k`}
                                />
                                <YAxis
                                    yAxisId="orders"
                                    orientation="right"
                                    tick={{ fontSize: 12, fill: "#6b7280" }}
                                    tickLine={false}
                                />
                                <Tooltip content={<CustomTooltip />} />
                                <Legend formatter={(value) => `${getSeriesStyleLabel(String(value))}: ${value}`} />
                                <Line
                                    yAxisId="sales"
                                    type="monotone"
                                    dataKey="sales"
                                    name="売上"
                                    stroke="#0ea5e9"
                                    strokeWidth={2}
                                    dot={{ fill: "#0ea5e9", r: 3 }}
                                />
                                <Line
                                    yAxisId="orders"
                                    type="monotone"
                                    dataKey="orders"
                                    name="注文数"
                                    stroke="#10b981"
                                    strokeWidth={2}
                                    strokeDasharray="6 4"
                                    dot={{ fill: "#10b981", r: 3 }}
                                />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                    {isTooltipVisible && activeItem && (
                        <div
                            role="status"
                            aria-live="polite"
                            className="mt-3 rounded-md border border-edge bg-surface p-3 text-sm text-ink-light"
                        >
                            <p className="font-medium text-ink mb-1">{activeItem.dateLabel}</p>
                            <p>[実線] 売上: {formatCurrency(activeItem.sales)}</p>
                            <p>[破線] 注文数: {activeItem.orders}件</p>
                        </div>
                    )}
                </DashboardChartErrorBoundary>
            </DashboardAsyncState>
        </GeoSurface>
    );
}
