import { act, renderHook } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { useMenuCategorySync } from "@/Hooks/useMenuCategorySync";
import type { CustomerMenuCategory } from "@/types";

function createCategories(ids: number[]): CustomerMenuCategory[] {
    return ids.map((id, index) => ({
        id,
        name: `カテゴリ${id}`,
        sort_order: index + 1,
        items: [],
    }));
}

describe("useMenuCategorySync", () => {
    it("カテゴリがないときは activeCategoryId と scrollToCategoryId が null になる", () => {
        const { result } = renderHook(() => useMenuCategorySync([]));

        expect(result.current.activeCategoryId).toBeNull();
        expect(result.current.scrollToCategoryId).toBeNull();
    });

    it("初回表示時は先頭カテゴリを activeCategoryId に使う", () => {
        const { result } = renderHook(() => useMenuCategorySync(createCategories([10, 20, 30])));

        expect(result.current.activeCategoryId).toBe(10);
        expect(result.current.scrollToCategoryId).toBeNull();
    });

    it("カテゴリタブ変更時に activeCategoryId と scrollToCategoryId を更新する", () => {
        const { result } = renderHook(() => useMenuCategorySync(createCategories([10, 20, 30])));

        act(() => {
            result.current.onCategoryTabChange(30);
        });

        expect(result.current.activeCategoryId).toBe(30);
        expect(result.current.scrollToCategoryId).toBe(30);
    });

    it("スクロール完了時に scrollToCategoryId を解放する", () => {
        const { result } = renderHook(() => useMenuCategorySync(createCategories([10, 20, 30])));

        act(() => {
            result.current.onCategoryTabChange(20);
        });
        expect(result.current.scrollToCategoryId).toBe(20);

        act(() => {
            result.current.onScrollComplete();
        });

        expect(result.current.activeCategoryId).toBe(20);
        expect(result.current.scrollToCategoryId).toBeNull();
    });

    it("現在の activeCategoryId が消えたら先頭の有効カテゴリへフォールバックする", () => {
        const { result, rerender } = renderHook(
            ({ categories }: { categories: CustomerMenuCategory[] }) => useMenuCategorySync(categories),
            {
                initialProps: { categories: createCategories([10, 20, 30]) },
            },
        );

        act(() => {
            result.current.onActiveCategoryChange(30);
        });
        expect(result.current.activeCategoryId).toBe(30);

        rerender({ categories: createCategories([10, 20]) });

        expect(result.current.activeCategoryId).toBe(10);
        expect(result.current.scrollToCategoryId).toBeNull();
    });

    it("後からカテゴリが来たときは先頭カテゴリを activeCategoryId に使う", () => {
        const { result, rerender } = renderHook(
            ({ categories }: { categories: CustomerMenuCategory[] }) => useMenuCategorySync(categories),
            {
                initialProps: { categories: [] },
            },
        );

        expect(result.current.activeCategoryId).toBeNull();

        rerender({ categories: createCategories([50, 60]) });

        expect(result.current.activeCategoryId).toBe(50);
        expect(result.current.scrollToCategoryId).toBeNull();
    });
});
