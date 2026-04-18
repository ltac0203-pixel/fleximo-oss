import { SalesPeriod } from "@/types";

interface PeriodSelectorProps {
    selected: SalesPeriod;
    onChange: (period: SalesPeriod) => void;
}

const periods: { value: SalesPeriod; label: string }[] = [
    { value: "daily", label: "日次" },
    { value: "weekly", label: "週次" },
    { value: "monthly", label: "月次" },
];

export default function PeriodSelector({ selected, onChange }: PeriodSelectorProps) {
    return (
        <div className="geo-surface inline-flex gap-1 p-1">
            {periods.map((period) => (
                <button
                    key={period.value}
                    onClick={() => onChange(period.value)}
                    className={`border px-3 py-1 text-sm transition ${
                        selected === period.value
                            ? "bg-sky-600 text-white border-sky-600 shadow-geo-sky"
                            : "bg-white text-ink-light border-edge-strong hover:bg-surface hover:border-primary-light"
                    }`}
                >
                    {period.label}
                </button>
            ))}
        </div>
    );
}
