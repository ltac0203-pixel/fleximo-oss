import { MAX_ITEM_QUANTITY } from "@/constants/quantity";

interface QuantityControlProps {
    quantity: number;
    onIncrease: () => void;
    onDecrease: () => void;
    onRemove?: () => void;
    disabled?: boolean;
    min?: number;
    max?: number;
}

export default function QuantityControl({
    quantity,
    onIncrease,
    onDecrease,
    onRemove,
    disabled = false,
    min = 1,
    max = MAX_ITEM_QUANTITY,
}: QuantityControlProps) {
    const handleDecrease = () => {
        if (quantity > min) {
            onDecrease();
        } else if (quantity === min && onRemove) {
            onRemove();
        }
    };

    return (
        <div className="flex items-center gap-1.5">
            <button
                type="button"
                onClick={handleDecrease}
                disabled={disabled}
                className="flex h-11 w-11 items-center justify-center border border-edge bg-white text-ink-light transition-colors hover:border-sky-300 hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-50"
                aria-label={quantity === min && onRemove ? "削除" : "減らす"}
            >
                {quantity === min && onRemove ? (
                    <svg className="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                ) : (
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 12H4" />
                    </svg>
                )}
            </button>
            <span className="w-9 border border-edge bg-surface px-1 py-2 text-center text-sm font-semibold text-ink">
                {quantity}
            </span>
            <button
                type="button"
                onClick={onIncrease}
                disabled={disabled || quantity >= max}
                className="flex h-11 w-11 items-center justify-center border border-edge bg-white text-ink-light transition-colors hover:border-sky-300 hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-50"
                aria-label="増やす"
            >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                </svg>
            </button>
        </div>
    );
}
