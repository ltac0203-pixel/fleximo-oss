import { CustomerMenuOptionGroup } from "@/types";

interface OptionGroupSelectorProps {
    group: CustomerMenuOptionGroup;
    selectedOptions: number[];
    onChange: (optionIds: number[]) => void;
}

export default function OptionGroupSelector({ group, selectedOptions, onChange }: OptionGroupSelectorProps) {
    const isSingleSelect = group.max_select === 1;

    const formatPrice = (price: number) => {
        if (price === 0) return "";
        return `+¥${price.toLocaleString()}`;
    };

    const handleSingleSelect = (optionId: number) => {
        onChange([optionId]);
    };

    const handleMultiSelect = (optionId: number, checked: boolean) => {
        if (checked) {
            // 制約違反をUI側で先に止め、送信時エラーと再操作の手間を減らす。
            if (selectedOptions.length >= group.max_select) {
                return;
            }
            onChange([...selectedOptions, optionId]);
        } else {
            onChange(selectedOptions.filter((id) => id !== optionId));
        }
    };

    const getSelectionLabel = () => {
        if (group.required) {
            if (group.min_select === group.max_select) {
                return `必須・${group.min_select}個選択`;
            }
            return `必須・${group.min_select}〜${group.max_select}個選択`;
        }
        if (group.max_select === 1) {
            return "任意・1個まで";
        }
        return `任意・${group.max_select}個まで`;
    };

    const headingId = `option-group-${group.id}-heading`;
    const limitHintId = `option-group-${group.id}-limit`;

    return (
        <div className="py-4 border-b last:border-b-0">
            <div className="flex items-center justify-between mb-3">
                <h4 id={headingId} className="font-medium text-ink">
                    {group.name}
                </h4>
                <span
                    className={`text-xs px-2.5 py-1 font-medium ${
                        group.required ? "bg-red-100 text-red-700" : "bg-surface-dim text-ink-light"
                    }`}
                >
                    {getSelectionLabel()}
                </span>
            </div>

            <div
                className="space-y-2"
                role={isSingleSelect ? "radiogroup" : "group"}
                aria-labelledby={headingId}
                aria-required={group.required || undefined}
            >
                {!isSingleSelect && (
                    <span id={limitHintId} className="sr-only">
                        最大{group.max_select}個まで選択できます
                    </span>
                )}
                {group.options.map((option) => {
                    const isSelected = selectedOptions.includes(option.id);
                    const isDisabled = !isSelected && !isSingleSelect && selectedOptions.length >= group.max_select;

                    if (isSingleSelect) {
                        return (
                            <label
                                key={option.id}
                                className={`flex items-center justify-between p-3.5 border cursor-pointer ${
                                    isSelected
                                        ? "border-2 border-sky-500 bg-sky-50 shadow-sm"
                                        : "border-edge hover:border-sky-300"
                                }`}
                            >
                                <div className="flex items-center gap-3">
                                    <input
                                        type="radio"
                                        name={`option-group-${group.id}`}
                                        checked={isSelected}
                                        onChange={() => handleSingleSelect(option.id)}
                                        className="w-5 h-5 text-sky-600 border-edge-strong focus:ring-primary"
                                    />
                                    <span className="text-ink">{option.name}</span>
                                </div>
                                {option.price > 0 && (
                                    <span className="text-sm text-muted">{formatPrice(option.price)}</span>
                                )}
                            </label>
                        );
                    }

                    return (
                        <label
                            key={option.id}
                            title={isDisabled ? "最大選択数に達しました" : undefined}
                            className={`flex items-center justify-between p-3.5 border ${
                                isDisabled
                                    ? "opacity-50 cursor-not-allowed border-edge"
                                    : isSelected
                                      ? "border-2 border-sky-500 bg-sky-50 shadow-sm cursor-pointer"
                                      : "border-edge hover:border-sky-300 cursor-pointer"
                            }`}
                        >
                            <div className="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    checked={isSelected}
                                    disabled={isDisabled}
                                    aria-describedby={isDisabled ? limitHintId : undefined}
                                    onChange={(e) => handleMultiSelect(option.id, e.target.checked)}
                                    className="w-5 h-5 text-sky-600 border-edge-strong rounded focus:ring-primary disabled:cursor-not-allowed"
                                />
                                <span className="text-ink">{option.name}</span>
                            </div>
                            {option.price > 0 && (
                                <span className="text-sm text-muted">{formatPrice(option.price)}</span>
                            )}
                        </label>
                    );
                })}
            </div>
        </div>
    );
}
