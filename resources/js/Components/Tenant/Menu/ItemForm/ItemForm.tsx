import { useCallback } from "react";
import { ItemFormProps } from "./types";
import BasicInfoSection from "./BasicInfoSection";
import CategorySelectionSection from "./CategorySelectionSection";
import AvailabilitySection from "./AvailabilitySection";
import AllergenNutritionSection from "./AllergenNutritionSection";
import OptionGroupSelectionSection from "./OptionGroupSelectionSection";

export default function ItemForm({
    formData,
    errors,
    categories,
    optionGroups,
    onChange,
    todayBusinessHours,
    businessHours,
    onActiveToggleRequest,
}: ItemFormProps) {
    const handleCategoryToggle = useCallback(
        (categoryId: number) => {
            const newIds = formData.category_ids.includes(categoryId)
                ? formData.category_ids.filter((id) => id !== categoryId)
                : [...formData.category_ids, categoryId];
            onChange({ ...formData, category_ids: newIds });
        },
        [formData, onChange],
    );

    const handleOptionGroupToggle = useCallback(
        (groupId: number) => {
            const newIds = formData.option_group_ids.includes(groupId)
                ? formData.option_group_ids.filter((id) => id !== groupId)
                : [...formData.option_group_ids, groupId];
            onChange({ ...formData, option_group_ids: newIds });
        },
        [formData, onChange],
    );

    return (
        <div className="space-y-6">
            <BasicInfoSection
                formData={formData}
                errors={errors}
                onChange={onChange}
                onActiveToggleRequest={onActiveToggleRequest}
            />

            <CategorySelectionSection
                formData={formData}
                errors={errors}
                categories={categories}
                onToggle={handleCategoryToggle}
            />

            <AvailabilitySection
                formData={formData}
                errors={errors}
                onChange={onChange}
                todayBusinessHours={todayBusinessHours}
                businessHours={businessHours}
            />

            <AllergenNutritionSection
                formData={formData}
                errors={errors}
                onChange={onChange}
            />

            <OptionGroupSelectionSection
                formData={formData}
                errors={errors}
                optionGroups={optionGroups}
                onToggle={handleOptionGroupToggle}
            />
        </div>
    );
}
