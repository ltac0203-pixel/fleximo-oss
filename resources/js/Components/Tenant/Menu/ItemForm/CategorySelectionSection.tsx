import InputError from "@/Components/InputError";
import { MenuCategory } from "@/types";
import { FormData, FormErrors } from "./types";

interface CategorySelectionSectionProps {
    formData: FormData;
    errors: FormErrors;
    categories: MenuCategory[];
    onToggle: (categoryId: number) => void;
}

export default function CategorySelectionSection({
    formData,
    errors,
    categories,
    onToggle,
}: CategorySelectionSectionProps) {
    return (
        <div className="space-y-4">
            <h3 className="text-lg font-medium text-ink">カテゴリ</h3>
            <InputError id="category_ids-error" message={errors.category_ids} className="mt-2" />

            {categories.length === 0 ? (
                <p className="text-sm text-muted">カテゴリがありません</p>
            ) : (
                <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                    {categories.map((category) => (
                        <label
                            key={category.id}
                            className={`flex items-center p-3 border cursor-pointer ${
                                formData.category_ids.includes(category.id)
                                    ? "border-primary bg-sky-50"
                                    : "border-edge hover:bg-surface"
                            }`}
                        >
                            <input
                                type="checkbox"
                                checked={formData.category_ids.includes(category.id)}
                                aria-invalid={!!errors.category_ids}
                                aria-describedby={errors.category_ids ? "category_ids-error" : undefined}
                                onChange={() => onToggle(category.id)}
                                className="h-4 w-4 text-primary-dark focus:ring-primary border-edge-strong rounded"
                            />
                            <span className="ml-2 text-sm text-ink">{category.name}</span>
                        </label>
                    ))}
                </div>
            )}
        </div>
    );
}
