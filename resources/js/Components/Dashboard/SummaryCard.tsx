import { formatPrice } from "@/Utils/formatPrice";
import GeoSurface from "@/Components/GeoSurface";

interface SummaryCardProps {
    title: string;
    value: string | number;
    subValue?: string;
    change?: number | null;
    changeLabel?: string;
}

function getChangeColor(change: number | null | undefined): string {
    if (change === null || change === undefined) return "text-muted";
    if (change > 0) return "text-green-600";
    if (change < 0) return "text-red-600";
    return "text-muted";
}

function getChangeIcon(change: number | null | undefined): string {
    if (change === null || change === undefined) return "";
    if (change > 0) return "↑";
    if (change < 0) return "↓";
    return "";
}

export default function SummaryCard({ title, value, subValue, change, changeLabel = "前日比" }: SummaryCardProps) {
    const displayValue = typeof value === "number" ? formatPrice(value) : value;

    return (
        <GeoSurface topAccent interactive hoverEffect="frame" className="p-4">
            <p className="text-sm text-ink-light">{title}</p>
            <p className="text-2xl font-semibold text-ink mt-1">{displayValue}</p>
            {subValue && <p className="text-sm text-muted mt-1">{subValue}</p>}
            {change !== undefined && (
                <p className={`text-sm mt-2 ${getChangeColor(change)}`}>
                    {change !== null ? (
                        <>
                            {getChangeIcon(change)} {Math.abs(change)}% {changeLabel}
                        </>
                    ) : (
                        <span className="text-muted-light">--</span>
                    )}
                </p>
            )}
        </GeoSurface>
    );
}
