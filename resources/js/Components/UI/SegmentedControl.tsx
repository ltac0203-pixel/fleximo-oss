interface SegmentedControlOption<T extends string> {
    value: T;
    label: string;
}

interface SegmentedControlProps<T extends string> {
    options: SegmentedControlOption<T>[];
    selected: T;
    onChange: (value: T) => void;
    ariaLabel?: string;
}

export default function SegmentedControl<T extends string>({
    options,
    selected,
    onChange,
    ariaLabel,
}: SegmentedControlProps<T>) {
    return (
        <div role="group" aria-label={ariaLabel} className="geo-surface inline-flex gap-1 p-1">
            {options.map((option) => {
                const isSelected = selected === option.value;
                return (
                    <button
                        key={option.value}
                        type="button"
                        onClick={() => onChange(option.value)}
                        aria-pressed={isSelected}
                        className={`border px-3 py-1 text-sm transition ${
                            isSelected
                                ? "bg-sky-600 text-white border-sky-600 shadow-geo-sky"
                                : "bg-white text-ink-light border-edge-strong hover:bg-surface hover:border-primary-light"
                        }`}
                    >
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}
