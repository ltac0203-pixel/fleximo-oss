import CategoryTabs from "@/Components/Customer/Menu/CategoryTabs";
import { CustomerMenuCategory } from "@/types";
import { render, screen, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

function createCategories(): CustomerMenuCategory[] {
    return [
        {
            id: 1,
            name: "カレー",
            sort_order: 1,
            items: [
                {
                    id: 101,
                    name: "ビーフカレー",
                    description: null,
                    price: 1200,
                    is_sold_out: false,
                    is_available: true,
                    available_from: null,
                    available_until: null,
                    available_days: 127,
                    option_groups: [],
                },
            ],
        },
        {
            id: 2,
            name: "ドリンク",
            sort_order: 2,
            items: [],
        },
        {
            id: 3,
            name: "デザート",
            sort_order: 3,
            items: [
                {
                    id: 301,
                    name: "プリン",
                    description: null,
                    price: 500,
                    is_sold_out: false,
                    is_available: true,
                    available_from: null,
                    available_until: null,
                    available_days: 127,
                    option_groups: [],
                },
                {
                    id: 302,
                    name: "チーズケーキ",
                    description: null,
                    price: 550,
                    is_sold_out: false,
                    is_available: true,
                    available_from: null,
                    available_until: null,
                    available_days: 127,
                    option_groups: [],
                },
            ],
        },
    ];
}

describe("CategoryTabs", () => {
    beforeEach(() => {
        vi.spyOn(window, "requestAnimationFrame").mockImplementation((callback: FrameRequestCallback) => {
            callback(0);
            return 0;
        });
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it("renders category names with item counts", () => {
        const onCategoryChange = vi.fn();
        render(<CategoryTabs categories={createCategories()} activeCategoryId={1} onCategoryChange={onCategoryChange} />);

        const curryTab = screen.getByRole("tab", { name: "カレー" });
        const drinkTab = screen.getByRole("tab", { name: "ドリンク" });
        const dessertTab = screen.getByRole("tab", { name: "デザート" });

        expect(curryTab).toBeInTheDocument();
        expect(drinkTab).toBeInTheDocument();
        expect(dessertTab).toBeInTheDocument();
        expect(curryTab).toHaveAttribute("aria-selected", "true");
        expect(within(curryTab).getByText("1")).toBeInTheDocument();
        expect(within(drinkTab).getByText("0")).toBeInTheDocument();
        expect(within(dessertTab).getByText("2")).toBeInTheDocument();
    });

    it("calls onCategoryChange when clicking a tab", async () => {
        const user = userEvent.setup();
        const onCategoryChange = vi.fn();
        render(<CategoryTabs categories={createCategories()} activeCategoryId={1} onCategoryChange={onCategoryChange} />);

        await user.click(screen.getByRole("tab", { name: "ドリンク" }));

        expect(onCategoryChange).toHaveBeenCalledWith(2);
    });

    it("moves focus and changes tab with ArrowRight/Home/End keys", async () => {
        const user = userEvent.setup();
        const onCategoryChange = vi.fn();
        render(<CategoryTabs categories={createCategories()} activeCategoryId={1} onCategoryChange={onCategoryChange} />);

        const curryTab = screen.getByRole("tab", { name: "カレー" });
        curryTab.focus();

        await user.keyboard("{ArrowRight}");
        expect(onCategoryChange).toHaveBeenCalledWith(2);

        const drinkTab = screen.getByRole("tab", { name: "ドリンク" });
        drinkTab.focus();
        await user.keyboard("{End}");
        expect(onCategoryChange).toHaveBeenCalledWith(3);

        const dessertTab = screen.getByRole("tab", { name: "デザート" });
        dessertTab.focus();
        await user.keyboard("{Home}");
        expect(onCategoryChange).toHaveBeenCalledWith(1);
    });
});
