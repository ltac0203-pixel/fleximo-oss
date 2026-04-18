interface DayOfWeekSelectorProps {
    value: number;
    onChange: (value: number) => void;
    disabled?: boolean;
    ariaDescribedBy?: string;
    ariaInvalid?: boolean;
}

// 複数曜日を1値で保持し、APIとDBの保存形式を軽量に保つ。
const DAYS = [
    { name: "日", flag: 1 },
    { name: "月", flag: 2 },
    { name: "火", flag: 4 },
    { name: "水", flag: 8 },
    { name: "木", flag: 16 },
    { name: "金", flag: 32 },
    { name: "土", flag: 64 },
];

const ALL_DAYS = 127;

export default function DayOfWeekSelector({
    value,
    onChange,
    disabled = false,
    ariaDescribedBy,
    ariaInvalid,
}: DayOfWeekSelectorProps) {
    const isSelected = (flag: number) => (value & flag) !== 0;

    const toggleDay = (flag: number) => {
        if (disabled) return;
        if (isSelected(flag)) {
            onChange(value & ~flag);
        } else {
            onChange(value | flag);
        }
    };

    const selectAll = () => {
        if (disabled) return;
        onChange(ALL_DAYS);
    };

    const clearAll = () => {
        if (disabled) return;
        onChange(0);
    };

    return (
        <div
            className="space-y-2"
            role="group"
            aria-describedby={ariaDescribedBy}
            aria-invalid={ariaInvalid ? true : undefined}
        >
            <div className="flex flex-wrap gap-2">
                {DAYS.map((day) => (
                    <button
                        key={day.flag}
                        type="button"
                        onClick={() => toggleDay(day.flag)}
                        disabled={disabled}
                        className={`w-12 h-12 rounded-full text-sm font-medium ${
                            isSelected(day.flag)
                                ? "bg-primary-dark text-white"
                                : "bg-surface-dim text-ink-light hover:bg-edge-strong"
                        } ${disabled ? "opacity-50 cursor-not-allowed" : "cursor-pointer"}`}
                    >
                        {day.name}
                    </button>
                ))}
            </div>
            <div className="flex gap-2 text-sm">
                <button
                    type="button"
                    onClick={selectAll}
                    disabled={disabled}
                    className="text-primary-dark hover:text-primary-dark disabled:opacity-50"
                >
                    全選択
                </button>
                <span className="text-muted-light">|</span>
                <button
                    type="button"
                    onClick={clearAll}
                    disabled={disabled}
                    className="text-ink-light hover:text-ink disabled:opacity-50"
                >
                    全解除
                </button>
            </div>
        </div>
    );
}
