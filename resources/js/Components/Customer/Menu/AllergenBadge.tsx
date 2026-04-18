import { getLabelsFromBitmask, MANDATORY_ALLERGENS, ADVISORY_ALLERGENS } from "@/constants/allergens";

interface AllergenBadgeProps {
    allergens: number;
    allergenAdvisories: number;
    allergenNote?: string | null;
    mode?: "compact" | "detail";
}

export default function AllergenBadge({ allergens, allergenAdvisories, allergenNote, mode = "compact" }: AllergenBadgeProps) {
    const mandatoryLabels = getLabelsFromBitmask(allergens, MANDATORY_ALLERGENS);
    const advisoryLabels = getLabelsFromBitmask(allergenAdvisories, ADVISORY_ALLERGENS);

    if (mandatoryLabels.length === 0 && advisoryLabels.length === 0 && !allergenNote) {
        return null;
    }

    if (mode === "compact") {
        return (
            <span className="inline-flex items-center border border-red-200 bg-red-50 px-1.5 py-0.5 text-[11px] font-medium text-red-700">
                アレルゲン
            </span>
        );
    }

    return (
        <div className="space-y-2">
            {mandatoryLabels.length > 0 && (
                <div>
                    <span className="text-xs font-medium text-red-700">特定原材料：</span>
                    <div className="mt-1 flex flex-wrap gap-1">
                        {mandatoryLabels.map((label) => (
                            <span
                                key={label}
                                className="rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-xs text-red-700"
                            >
                                {label}
                            </span>
                        ))}
                    </div>
                </div>
            )}
            {advisoryLabels.length > 0 && (
                <div>
                    <span className="text-xs font-medium text-orange-700">推奨表示：</span>
                    <div className="mt-1 flex flex-wrap gap-1">
                        {advisoryLabels.map((label) => (
                            <span
                                key={label}
                                className="rounded-full border border-orange-200 bg-orange-50 px-2 py-0.5 text-xs text-orange-700"
                            >
                                {label}
                            </span>
                        ))}
                    </div>
                </div>
            )}
            {allergenNote && (
                <p className="text-xs text-muted">{allergenNote}</p>
            )}
        </div>
    );
}
