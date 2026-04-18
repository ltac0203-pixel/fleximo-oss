interface QuantitySelectorProps {
    value: number;
    onChange: (quantity: number) => void;
    min?: number;
    max?: number;
}

export default function QuantitySelector({ value, onChange, min = 1, max = 99 }: QuantitySelectorProps) {
    const canDecrement = value > min;
    const canIncrement = value < max;

    const handleDecrement = () => {
        if (canDecrement) {
            onChange(value - 1);
        }
    };

    const handleIncrement = () => {
        if (canIncrement) {
            onChange(value + 1);
        }
    };

    return (
        <div className="flex items-center gap-3">
            <button
                type="button"
                onClick={handleDecrement}
                disabled={!canDecrement}
                className={`w-12 h-12 flex items-center justify-center rounded-full border text-xl font-medium ${
                    canDecrement
                        ? "border-edge-strong text-ink-light hover:border-edge-strong hover:bg-surface active:bg-surface-dim"
                        : "border-edge text-muted-light cursor-not-allowed"
                }`}
                aria-label="数量を減らす"
            >
                -
            </button>
            <span className="w-10 text-center text-xl font-semibold text-ink">{value}</span>
            <button
                type="button"
                onClick={handleIncrement}
                disabled={!canIncrement}
                className={`w-12 h-12 flex items-center justify-center rounded-full border text-xl font-medium ${
                    canIncrement
                        ? "border-edge-strong text-ink-light hover:border-edge-strong hover:bg-surface active:bg-surface-dim"
                        : "border-edge text-muted-light cursor-not-allowed"
                }`}
                aria-label="数量を増やす"
            >
                +
            </button>
        </div>
    );
}
