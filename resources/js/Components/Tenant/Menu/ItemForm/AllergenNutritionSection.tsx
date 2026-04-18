import { useCallback } from "react";
import { FormData, FormErrors } from "./types";
import {
    MANDATORY_ALLERGENS,
    ADVISORY_ALLERGENS,
    NUTRITION_FIELDS,
    hasBit,
    toggleBit,
} from "@/constants/allergens";

interface AllergenNutritionSectionProps {
    formData: FormData;
    errors: FormErrors;
    onChange: (data: FormData) => void;
}

export default function AllergenNutritionSection({ formData, errors, onChange }: AllergenNutritionSectionProps) {
    const handleAllergenToggle = useCallback(
        (bit: number) => {
            onChange({ ...formData, allergens: toggleBit(formData.allergens, bit) });
        },
        [formData, onChange],
    );

    const handleAdvisoryToggle = useCallback(
        (bit: number) => {
            onChange({ ...formData, allergen_advisories: toggleBit(formData.allergen_advisories, bit) });
        },
        [formData, onChange],
    );

    const handleNutritionChange = useCallback(
        (key: string, value: string) => {
            onChange({
                ...formData,
                nutrition_info: {
                    ...formData.nutrition_info,
                    [key]: value === "" ? "" : Number(value),
                },
            });
        },
        [formData, onChange],
    );

    return (
        <div className="space-y-6">
            {/* 特定原材料（表示義務） */}
            <div>
                <label className="mb-2 block text-sm font-medium text-ink-light">
                    特定原材料（表示義務）
                </label>
                <div className="flex flex-wrap gap-2">
                    {MANDATORY_ALLERGENS.map((allergen) => (
                        <button
                            key={allergen.bit}
                            type="button"
                            onClick={() => handleAllergenToggle(allergen.bit)}
                            className={`rounded-full border px-3 py-1 text-sm transition-colors ${
                                hasBit(formData.allergens, allergen.bit)
                                    ? "border-red-300 bg-red-50 text-red-700"
                                    : "border-edge bg-white text-ink-light hover:border-edge-strong"
                            }`}
                        >
                            {allergen.label}
                        </button>
                    ))}
                </div>
                {errors.allergens && <p className="mt-1 text-sm text-red-600">{errors.allergens}</p>}
            </div>

            {/* 推奨表示品目 */}
            <div>
                <label className="mb-2 block text-sm font-medium text-ink-light">
                    特定原材料に準ずるもの（推奨表示）
                </label>
                <div className="flex flex-wrap gap-2">
                    {ADVISORY_ALLERGENS.map((allergen) => (
                        <button
                            key={allergen.bit}
                            type="button"
                            onClick={() => handleAdvisoryToggle(allergen.bit)}
                            className={`rounded-full border px-3 py-1 text-sm transition-colors ${
                                hasBit(formData.allergen_advisories, allergen.bit)
                                    ? "border-orange-300 bg-orange-50 text-orange-700"
                                    : "border-edge bg-white text-ink-light hover:border-edge-strong"
                            }`}
                        >
                            {allergen.label}
                        </button>
                    ))}
                </div>
                {errors.allergen_advisories && <p className="mt-1 text-sm text-red-600">{errors.allergen_advisories}</p>}
            </div>

            {/* アレルゲン備考 */}
            <div>
                <label htmlFor="allergen_note" className="mb-1 block text-sm font-medium text-ink-light">
                    アレルゲン備考
                </label>
                <textarea
                    id="allergen_note"
                    value={formData.allergen_note}
                    onChange={(e) => onChange({ ...formData, allergen_note: e.target.value })}
                    placeholder="例: 同一工場で卵・乳を含む製品を製造しています"
                    rows={2}
                    maxLength={500}
                    className="w-full rounded-md border-edge-strong shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                />
                {errors.allergen_note && <p className="mt-1 text-sm text-red-600">{errors.allergen_note}</p>}
            </div>

            {/* 栄養成分 */}
            <div>
                <label className="mb-2 block text-sm font-medium text-ink-light">
                    栄養成分表示（1食あたり）
                </label>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    {NUTRITION_FIELDS.map((field) => (
                        <div key={field.key}>
                            <label htmlFor={`nutrition_${field.key}`} className="mb-1 block text-xs text-muted">
                                {field.label}（{field.unit}）
                            </label>
                            <input
                                id={`nutrition_${field.key}`}
                                type="number"
                                min="0"
                                step="0.1"
                                value={formData.nutrition_info[field.key as keyof typeof formData.nutrition_info]}
                                onChange={(e) => handleNutritionChange(field.key, e.target.value)}
                                className="w-full rounded-md border-edge-strong shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                placeholder="--"
                            />
                            {errors[`nutrition_info.${field.key}` as keyof FormErrors] && (
                                <p className="mt-1 text-xs text-red-600">
                                    {errors[`nutrition_info.${field.key}` as keyof FormErrors]}
                                </p>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
