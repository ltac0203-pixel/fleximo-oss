interface TimeRangeInputProps {
    fromValue: string | null;
    untilValue: string | null;
    onFromChange: (value: string | null) => void;
    onUntilChange: (value: string | null) => void;
    disabled?: boolean;
    fromId?: string;
    untilId?: string;
    fromAriaDescribedBy?: string;
    untilAriaDescribedBy?: string;
    fromInvalid?: boolean;
    untilInvalid?: boolean;
}

export default function TimeRangeInput({
    fromValue,
    untilValue,
    onFromChange,
    onUntilChange,
    disabled = false,
    fromId,
    untilId,
    fromAriaDescribedBy,
    untilAriaDescribedBy,
    fromInvalid,
    untilInvalid,
}: TimeRangeInputProps) {
    const handleFromChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        onFromChange(value || null);
    };

    const handleUntilChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        onUntilChange(value || null);
    };

    const clearTimeRange = () => {
        onFromChange(null);
        onUntilChange(null);
    };

    return (
        <div className="space-y-2">
            <div className="flex items-center gap-2">
                <input
                    type="time"
                    value={fromValue || ""}
                    id={fromId}
                    aria-describedby={fromAriaDescribedBy}
                    aria-invalid={fromInvalid ? true : undefined}
                    onChange={handleFromChange}
                    disabled={disabled}
                    className="block w-32 rounded-md border-edge-strong focus:border-primary focus:ring-primary sm:text-sm disabled:bg-surface-dim"
                />
                <span className="text-muted">〜</span>
                <input
                    type="time"
                    value={untilValue || ""}
                    id={untilId}
                    aria-describedby={untilAriaDescribedBy}
                    aria-invalid={untilInvalid ? true : undefined}
                    onChange={handleUntilChange}
                    disabled={disabled}
                    className="block w-32 rounded-md border-edge-strong focus:border-primary focus:ring-primary sm:text-sm disabled:bg-surface-dim"
                />
                {(fromValue || untilValue) && !disabled && (
                    <button
                        type="button"
                        onClick={clearTimeRange}
                        className="text-sm text-muted hover:text-ink-light"
                    >
                        クリア
                    </button>
                )}
            </div>
            <p className="text-xs text-muted">時間帯を設定しない場合は終日販売となります</p>
        </div>
    );
}
