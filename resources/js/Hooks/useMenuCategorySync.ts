import { CustomerMenuCategory } from "@/types";
import { useState } from "react";

interface UseMenuCategorySyncResult {
    activeCategoryId: number | null;
    scrollToCategoryId: number | null;
    onCategoryTabChange: (categoryId: number) => void;
    onActiveCategoryChange: (categoryId: number) => void;
    onScrollComplete: () => void;
}

export function useMenuCategorySync(categories: CustomerMenuCategory[]): UseMenuCategorySyncResult {
    const initialCategoryId = categories[0]?.id ?? null;

    const [requestedActiveCategoryId, setRequestedActiveCategoryId] = useState<number | null>(initialCategoryId);
    const [scrollToCategoryId, setScrollToCategoryId] = useState<number | null>(null);

    const activeCategoryId =
        requestedActiveCategoryId !== null && categories.some((category) => category.id === requestedActiveCategoryId)
            ? requestedActiveCategoryId
            : initialCategoryId;
    const nextScrollTargetId =
        scrollToCategoryId !== null && categories.some((category) => category.id === scrollToCategoryId)
            ? scrollToCategoryId
            : null;

    const onCategoryTabChange = (categoryId: number) => {
        setRequestedActiveCategoryId(categoryId);
        setScrollToCategoryId(categoryId);
    };

    const onActiveCategoryChange = (categoryId: number) => {
        setRequestedActiveCategoryId(categoryId);
    };

    const onScrollComplete = () => {
        setScrollToCategoryId(null);
    };

    return {
        activeCategoryId,
        scrollToCategoryId: nextScrollTargetId,
        onCategoryTabChange,
        onActiveCategoryChange,
        onScrollComplete,
    };
}
