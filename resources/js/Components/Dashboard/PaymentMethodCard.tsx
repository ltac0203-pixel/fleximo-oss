import { useId } from "react";
import { PieChart, Pie, Cell, Tooltip, ResponsiveContainer } from "recharts";
import type { TooltipContentProps, TooltipPayloadEntry } from "recharts";
import { usePaymentMethodData } from "@/Hooks/usePaymentMethodData";
import { PaymentMethodStatsItem } from "@/types";
import { formatCurrency, formatNumber } from "@/Utils/formatPrice";
import GeoSurface from "@/Components/GeoSurface";
import DateRangeSelector from "./DateRangeSelector";
import DashboardChartErrorBoundary from "./DashboardChartErrorBoundary";
import Spinner from "@/Components/Loading/Spinner";
import useKeyboardChartNavigation from "./useKeyboardChartNavigation";

const METHOD_COLORS: Record<string, string> = {
    card: "#3b82f6",
    paypay: "#ef4444",
};
const METHOD_PATTERNS: Record<string, string> = {
    card: "斜線",
    paypay: "ドット",
};

function getPatternLabel(method: string): string {
    return METHOD_PATTERNS[method] ?? "パターン";
}

type PaymentMethodChartItem = PaymentMethodStatsItem & { color: string };

type PaymentMethodTooltipPayload = TooltipPayloadEntry<number, string> & { payload?: PaymentMethodChartItem };
type CustomTooltipProps = Partial<Pick<TooltipContentProps<number, string>, "active" | "payload">>;

function isPaymentMethodChartItem(value: unknown): value is PaymentMethodChartItem {
    if (typeof value !== "object" || value === null) {
        return false;
    }

    const candidate = value as Partial<PaymentMethodChartItem>;
    return (
        typeof candidate.method === "string" &&
        typeof candidate.label === "string" &&
        typeof candidate.count === "number" &&
        typeof candidate.amount === "number" &&
        typeof candidate.color === "string"
    );
}

function CustomTooltip({ active, payload }: CustomTooltipProps) {
    const firstEntry = payload?.[0] as PaymentMethodTooltipPayload | undefined;
    const item = firstEntry?.payload;
    if (!active || !isPaymentMethodChartItem(item)) return null;

    return (
        <div className="bg-white border border-edge p-3 shadow-sm">
            <p className="text-sm font-medium text-ink mb-1">{item.label}</p>
            <p className="text-xs text-muted mb-1">識別: {getPatternLabel(item.method)}</p>
            <p className="text-sm text-ink-light">{formatNumber(item.count)}件</p>
            <p className="text-sm text-ink-light">{formatCurrency(item.amount)}</p>
        </div>
    );
}

export default function PaymentMethodCard() {
    const { range, data, loading, fetchError, onRangeChange } = usePaymentMethodData();

    const chartData = data.methods
        .filter((method) => method.count > 0)
        .map((method) => ({
            ...method,
            color: METHOD_COLORS[method.method] || "#94a3b8",
        }));
    const chartDescriptionId = useId();
    const patternIdPrefix = useId().replace(/:/g, "-");
    const { activeItem, isTooltipVisible, onFocus, onBlur, onKeyDown } = useKeyboardChartNavigation(chartData);

    function getPatternId(method: string): string {
        return `payment-method-pattern-${patternIdPrefix}-${method}`;
    }

    return (
        <GeoSurface topAccent elevated className="p-4">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-medium text-ink">決済方法別</h3>
                <DateRangeSelector selected={range} onChange={onRangeChange} />
            </div>

            {loading ? (
                <div className="h-64 flex items-center justify-center">
                    <Spinner variant="muted" />
                </div>
            ) : fetchError ? (
                <div className="h-64 flex items-center justify-center text-red-500">データの取得に失敗しました</div>
            ) : chartData.length === 0 ? (
                <div className="h-64 flex items-center justify-center text-muted">データがありません</div>
            ) : (
                <div className="flex flex-col lg:flex-row gap-4">
                    <div className="flex-1">
                        <DashboardChartErrorBoundary heightClassName="h-56">
                            <p id={chartDescriptionId} className="sr-only">
                                チャートにフォーカスした後、左右矢印キーで決済方法ごとの詳細を確認できます。
                            </p>
                            <div
                                className="h-56 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                role="group"
                                tabIndex={0}
                                aria-label="決済方法別チャート"
                                aria-describedby={chartDescriptionId}
                                onFocus={onFocus}
                                onBlur={onBlur}
                                onKeyDown={onKeyDown}
                            >
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart accessibilityLayer>
                                        <defs>
                                            {chartData.map((item) => (
                                                <pattern
                                                    key={`pattern-${item.method}`}
                                                    id={getPatternId(item.method)}
                                                    width="8"
                                                    height="8"
                                                    patternUnits="userSpaceOnUse"
                                                >
                                                    <rect width="8" height="8" fill={item.color} />
                                                    {item.method === "card" ? (
                                                        <path
                                                            d="M0 8L8 0M-2 2L2 -2M6 10L10 6"
                                                            stroke="#ffffff"
                                                            strokeWidth="1.4"
                                                            opacity="0.9"
                                                        />
                                                    ) : (
                                                        <>
                                                            <circle cx="2" cy="2" r="1" fill="#ffffff" opacity="0.9" />
                                                            <circle cx="6" cy="2" r="1" fill="#ffffff" opacity="0.9" />
                                                            <circle cx="4" cy="6" r="1" fill="#ffffff" opacity="0.9" />
                                                        </>
                                                    )}
                                                </pattern>
                                            ))}
                                        </defs>
                                        <Pie
                                            data={chartData}
                                            dataKey="amount"
                                            nameKey="label"
                                            cx="50%"
                                            cy="50%"
                                            outerRadius={80}
                                            paddingAngle={2}
                                        >
                                            {chartData.map((entry) => (
                                                <Cell
                                                    key={entry.method}
                                                    fill={`url(#${getPatternId(entry.method)})`}
                                                    stroke={entry.color}
                                                    strokeWidth={1.5}
                                                />
                                            ))}
                                        </Pie>
                                        <Tooltip content={<CustomTooltip />} />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                            {isTooltipVisible && activeItem && (
                                <div
                                    role="status"
                                    aria-live="polite"
                                    className="mt-3 rounded-md border border-edge bg-surface p-3 text-sm text-ink-light"
                                >
                                    <p className="font-medium text-ink mb-1">
                                        {activeItem.label} [{getPatternLabel(activeItem.method)}]
                                    </p>
                                    <p>{formatNumber(activeItem.count)}件</p>
                                    <p>{formatCurrency(activeItem.amount)}</p>
                                </div>
                            )}
                        </DashboardChartErrorBoundary>
                    </div>
                    <div className="flex-1 space-y-3">
                        {chartData.map((item) => (
                            <div key={item.method} className="space-y-1">
                                <div className="flex items-center gap-2">
                                    <span
                                        className="w-3 h-3 rounded-full flex-shrink-0"
                                        style={{
                                            backgroundColor: item.color,
                                        }}
                                    />
                                    <span className="text-sm font-medium text-ink">{item.label}</span>
                                    <span className="text-xs text-muted">[{getPatternLabel(item.method)}]</span>
                                </div>
                                <div className="ml-5 text-sm text-ink-light">
                                    <span>{formatNumber(item.count)}件</span>
                                    <span className="mx-2">|</span>
                                    <span className="font-medium text-ink">{formatCurrency(item.amount)}</span>
                                    {data.total_amount > 0 && (
                                        <span className="text-muted-light ml-1">
                                            ({((item.amount / data.total_amount) * 100).toFixed(1)}
                                            %)
                                        </span>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </GeoSurface>
    );
}
