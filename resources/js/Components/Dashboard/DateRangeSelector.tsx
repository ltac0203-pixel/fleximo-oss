export type DateRange = "today" | "week" | "month";

interface DateRangeSelectorProps {
    selected: DateRange;
    onChange: (range: DateRange) => void;
}

const ranges: { value: DateRange; label: string }[] = [
    { value: "today", label: "今日" },
    { value: "week", label: "今週" },
    { value: "month", label: "今月" },
];

// クライアント側で期間境界を統一し、API呼び出しごとの条件ぶれを防ぐ。
export function getDateRangeParams(range: DateRange): {
    start_date: string;
    end_date: string;
} {
    const today = new Date();
    const endDate = today.toISOString().split("T")[0];

    let startDate: string;
    if (range === "today") {
        startDate = endDate;
    } else if (range === "week") {
        const weekAgo = new Date(today);
        weekAgo.setDate(weekAgo.getDate() - 6);
        startDate = weekAgo.toISOString().split("T")[0];
    } else {
        const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
        startDate = monthStart.toISOString().split("T")[0];
    }

    return { start_date: startDate, end_date: endDate };
}

export default function DateRangeSelector({ selected, onChange }: DateRangeSelectorProps) {
    return (
        <div className="geo-surface inline-flex gap-1 p-1">
            {ranges.map((r) => (
                <button
                    key={r.value}
                    onClick={() => onChange(r.value)}
                    className={`border px-3 py-1 text-sm transition ${
                        selected === r.value
                            ? "bg-sky-600 text-white border-sky-600 shadow-geo-sky"
                            : "bg-white text-ink-light border-edge-strong hover:bg-surface hover:border-primary-light"
                    }`}
                >
                    {r.label}
                </button>
            ))}
        </div>
    );
}
