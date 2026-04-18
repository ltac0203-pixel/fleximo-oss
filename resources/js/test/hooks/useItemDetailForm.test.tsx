import { renderHook, act, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { useItemDetailForm } from "@/Hooks/useItemDetailForm";
import { CustomerMenuItem } from "@/types";

const mockItem: CustomerMenuItem = {
    id: 1,
    name: "テスト商品",
    description: null,
    price: 1000,
    image_url: null,
    is_sold_out: false,
    option_groups: [
        {
            id: 10,
            name: "サイズ",
            required: true,
            min_select: 1,
            max_select: 1,
            options: [
                { id: 100, name: "S", price: 0 },
                { id: 101, name: "M", price: 100 },
            ],
        },
        {
            id: 20,
            name: "トッピング",
            required: false,
            min_select: 0,
            max_select: 2,
            options: [
                { id: 200, name: "チーズ", price: 50 },
                { id: 201, name: "卵", price: 30 },
            ],
        },
    ],
};

describe("useItemDetailForm", () => {
    const onAddToCart = vi.fn();
    const onClose = vi.fn();

    beforeEach(() => {
        vi.clearAllMocks();
    });

    function renderUseItemDetailForm(show = true, item: CustomerMenuItem | null = mockItem) {
        return renderHook(({ show, item }) => useItemDetailForm({ show, item, onAddToCart, onClose }), {
            initialProps: { show, item },
        });
    }

    it("isValid: required=true かつ min_select=0 でも未選択なら false", () => {
        const requiredWithZeroMinItem: CustomerMenuItem = {
            ...mockItem,
            option_groups: [
                {
                    ...mockItem.option_groups[0],
                    required: true,
                    min_select: 0,
                },
            ],
        };

        const { result } = renderUseItemDetailForm(true, requiredWithZeroMinItem);
        expect(result.current.isValid).toBe(false);

        act(() => {
            result.current.handleOptionChange(requiredWithZeroMinItem.option_groups[0].id, [100]);
        });

        expect(result.current.isValid).toBe(true);
    });

    it("初期状態: quantity=1, selectedOptionsByGroup=各グループ空配列", () => {
        const { result } = renderUseItemDetailForm();
        expect(result.current.quantity).toBe(1);
        expect(result.current.selectedOptionsByGroup).toEqual({
            10: [],
            20: [],
        });
    });

    it("isValid: 必須グループの min_select 未満のとき false", () => {
        const { result } = renderUseItemDetailForm();
        expect(result.current.isValid).toBe(false);
    });

    it("isValid: 必須グループの min_select 以上のとき true", () => {
        const { result } = renderUseItemDetailForm();
        act(() => {
            result.current.handleOptionChange(10, [100]);
        });
        expect(result.current.isValid).toBe(true);
    });

    it("isValid: 任意グループは選択なしでも true（必須グループが満たされている場合）", () => {
        const { result } = renderUseItemDetailForm();
        act(() => {
            result.current.handleOptionChange(10, [100]);
        });
        expect(result.current.isValid).toBe(true);
    });

    it("handleOptionChange: グループのオプション選択が追跡される", () => {
        const { result } = renderUseItemDetailForm();
        act(() => {
            result.current.handleOptionChange(20, [200, 201]);
        });
        expect(result.current.selectedOptionsByGroup[20]).toEqual([200, 201]);
    });

    it("selectedOptions: 全グループの選択オプションがフラット化される", () => {
        const { result } = renderUseItemDetailForm();
        act(() => {
            result.current.handleOptionChange(10, [100]);
            result.current.handleOptionChange(20, [200]);
        });
        const options = result.current.selectedOptions;
        expect(options).toHaveLength(2);
        expect(options.map((o) => o.id)).toContain(100);
        expect(options.map((o) => o.id)).toContain(200);
    });

    it("handleAddToCart: isValid=true のとき onAddToCart が呼ばれる", () => {
        const { result } = renderUseItemDetailForm();
        act(() => {
            result.current.handleOptionChange(10, [100]);
        });
        act(() => {
            result.current.handleAddToCart();
        });
        expect(onAddToCart).toHaveBeenCalledWith({
            menuItemId: 1,
            quantity: 1,
            selectedOptions: [100],
        });
        expect(onClose).toHaveBeenCalled();
    });

    it("handleAddToCart: isValid=false のとき onAddToCart が呼ばれない", () => {
        const { result } = renderUseItemDetailForm();
        act(() => {
            result.current.handleAddToCart();
        });
        expect(onAddToCart).not.toHaveBeenCalled();
    });

    it("show 変更時にリセットされる（quantity=1、selectedOptions=空）", async () => {
        const { result, rerender } = renderUseItemDetailForm();

        act(() => {
            result.current.handleOptionChange(10, [100]);
            result.current.setQuantity(3);
        });
        expect(result.current.quantity).toBe(3);
        expect(result.current.selectedOptionsByGroup[10]).toEqual([100]);

        rerender({ show: false, item: mockItem });
        rerender({ show: true, item: mockItem });

        await waitFor(() => {
            expect(result.current.quantity).toBe(1);
            expect(result.current.selectedOptionsByGroup).toEqual({
                10: [],
                20: [],
            });
        });
    });
});
