import { useState } from "react";
import { NutritionInfo } from "@/types";
import { NUTRITION_FIELDS } from "@/constants/allergens";

interface NutritionTableProps {
    nutritionInfo: NutritionInfo;
}

export default function NutritionTable({ nutritionInfo }: NutritionTableProps) {
    const [isOpen, setIsOpen] = useState(false);

    const hasAnyValue = NUTRITION_FIELDS.some(
        (field) => nutritionInfo[field.key as keyof NutritionInfo] != null,
    );

    if (!hasAnyValue) {
        return null;
    }

    return (
        <div className="border-t border-surface-dim pt-3">
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className="flex w-full items-center justify-between text-sm text-ink-light hover:text-ink"
                aria-expanded={isOpen}
            >
                <span className="font-medium">栄養成分表示</span>
                <svg
                    className={`h-4 w-4 transition-transform ${isOpen ? "rotate-180" : ""}`}
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            {isOpen && (
                <table className="mt-2 w-full text-sm">
                    <tbody>
                        {NUTRITION_FIELDS.map((field) => {
                            const value = nutritionInfo[field.key as keyof NutritionInfo];
                            if (value == null) return null;
                            return (
                                <tr key={field.key} className="border-b border-surface">
                                    <td className="py-1.5 text-muted">{field.label}</td>
                                    <td className="py-1.5 text-right text-ink-light">
                                        {value}{field.unit}
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            )}
        </div>
    );
}
