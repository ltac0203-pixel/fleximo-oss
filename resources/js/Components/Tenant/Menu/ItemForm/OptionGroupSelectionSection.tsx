import InputError from "@/Components/InputError";
import { OptionGroup } from "@/types";
import { FormData, FormErrors } from "./types";

interface OptionGroupSelectionSectionProps {
    formData: FormData;
    errors: FormErrors;
    optionGroups: OptionGroup[];
    onToggle: (groupId: number) => void;
}

export default function OptionGroupSelectionSection({
    formData,
    errors,
    optionGroups,
    onToggle,
}: OptionGroupSelectionSectionProps) {
    return (
        <div className="space-y-4">
            <h3 className="text-lg font-medium text-ink">オプショングループ</h3>
            <InputError id="option_group_ids-error" message={errors.option_group_ids} className="mt-2" />

            {optionGroups.length === 0 ? (
                <p className="text-sm text-muted">オプショングループがありません</p>
            ) : (
                <div className="space-y-2">
                    {optionGroups.map((group) => (
                        <label
                            key={group.id}
                            className={`flex items-center justify-between p-3 border cursor-pointer ${
                                formData.option_group_ids.includes(group.id)
                                    ? "border-primary bg-sky-50"
                                    : "border-edge hover:bg-surface"
                            }`}
                        >
                            <div className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={formData.option_group_ids.includes(group.id)}
                                    aria-invalid={!!errors.option_group_ids}
                                    aria-describedby={errors.option_group_ids ? "option_group_ids-error" : undefined}
                                    onChange={() => onToggle(group.id)}
                                    className="h-4 w-4 text-primary-dark focus:ring-primary border-edge-strong rounded"
                                />
                                <span className="ml-2 text-sm text-ink">{group.name}</span>
                            </div>
                            <span className="text-xs text-muted">
                                {group.required ? "必須" : "任意"}
                                {group.max_select > 1 && ` (最大${group.max_select}個)`}
                            </span>
                        </label>
                    ))}
                </div>
            )}
        </div>
    );
}
