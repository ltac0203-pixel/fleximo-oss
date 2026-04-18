import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { beforeEach, describe, expect, it, vi } from "vitest";
import MenuItemCard from "@/Components/Customer/Menu/MenuItemCard";
import { CustomerMenuItem } from "@/types";

const formatPriceMock = vi.hoisted(() => vi.fn((price: number) => `¥${price}`));

vi.mock("@/Utils/formatPrice", () => ({
    formatPrice: formatPriceMock,
}));

function createItem(overrides: Partial<CustomerMenuItem> = {}): CustomerMenuItem {
    return {
        id: 1,
        name: "カレー",
        description: "スパイスカレー",
        price: 1200,
        is_sold_out: false,
        is_available: true,
        available_from: null,
        available_until: null,
        available_days: 127,
        option_groups: [
            {
                id: 10,
                name: "サイズ",
                required: false,
                min_select: 0,
                max_select: 1,
                options: [{ id: 100, name: "大盛り", price: 150 }],
            },
        ],
        ...overrides,
    };
}

function cloneItem(item: CustomerMenuItem): CustomerMenuItem {
    return {
        ...item,
        option_groups: item.option_groups.map((group) => ({
            ...group,
            options: group.options.map((option) => ({ ...option })),
        })),
    };
}

function createOnItemClickRef(handler = vi.fn()) {
    return { current: handler };
}

describe("MenuItemCard", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        formatPriceMock.mockClear();
    });

    it("does not rerender when item is a new reference but value-equal", () => {
        const onItemClickRef = createOnItemClickRef();
        const item = createItem();
        const { rerender } = render(<MenuItemCard item={item} onItemClickRef={onItemClickRef} />);

        const initialFormatCalls = formatPriceMock.mock.calls.length;

        rerender(<MenuItemCard item={cloneItem(item)} onItemClickRef={onItemClickRef} />);

        expect(formatPriceMock.mock.calls.length).toBe(initialFormatCalls);
    });

    it("rerenders when display-affecting item fields change", () => {
        const onItemClickRef = createOnItemClickRef();
        const item = createItem();
        const { rerender } = render(<MenuItemCard item={item} onItemClickRef={onItemClickRef} />);

        const initialFormatCalls = formatPriceMock.mock.calls.length;
        const updatedItem = createItem({ name: "チーズカレー" });

        rerender(<MenuItemCard item={updatedItem} onItemClickRef={onItemClickRef} />);

        expect(formatPriceMock.mock.calls.length).toBe(initialFormatCalls + 1);
        expect(screen.getByText("チーズカレー")).toBeInTheDocument();
    });

    it("rerenders when option_groups change", () => {
        const onItemClickRef = createOnItemClickRef();
        const item = createItem();
        const { rerender } = render(<MenuItemCard item={item} onItemClickRef={onItemClickRef} />);

        const initialFormatCalls = formatPriceMock.mock.calls.length;
        const updatedItem = createItem({
            option_groups: [
                {
                    ...item.option_groups[0],
                    options: [...item.option_groups[0].options, { id: 101, name: "温玉", price: 120 }],
                },
            ],
        });

        rerender(<MenuItemCard item={updatedItem} onItemClickRef={onItemClickRef} />);

        expect(formatPriceMock.mock.calls.length).toBe(initialFormatCalls + 1);
    });

    it("does not rerender when onItemClick callback implementation changes via ref", async () => {
        const user = userEvent.setup();
        const firstOnItemClick = vi.fn();
        const secondOnItemClick = vi.fn();
        const onItemClickRef = createOnItemClickRef(firstOnItemClick);
        const item = createItem();
        const { rerender } = render(<MenuItemCard item={item} onItemClickRef={onItemClickRef} />);

        const initialFormatCalls = formatPriceMock.mock.calls.length;
        onItemClickRef.current = secondOnItemClick;

        rerender(<MenuItemCard item={item} onItemClickRef={onItemClickRef} />);

        expect(formatPriceMock.mock.calls.length).toBe(initialFormatCalls);

        await user.click(screen.getByRole("button"));

        expect(firstOnItemClick).not.toHaveBeenCalled();
        expect(secondOnItemClick).toHaveBeenCalledWith(item);
    });

    it("invokes onItemClick with the latest item data", async () => {
        const user = userEvent.setup();
        const onItemClick = vi.fn();
        const onItemClickRef = createOnItemClickRef(onItemClick);
        const item = createItem();
        const { rerender } = render(<MenuItemCard item={item} onItemClickRef={onItemClickRef} />);
        const updatedItem = createItem({ id: 2, name: "キーマカレー", price: 980 });

        rerender(<MenuItemCard item={updatedItem} onItemClickRef={onItemClickRef} />);
        await user.click(screen.getByRole("button"));

        expect(onItemClick).toHaveBeenCalledWith(updatedItem);
    });

    it("shows description when item.description exists", () => {
        const onItemClickRef = createOnItemClickRef();
        render(<MenuItemCard item={createItem({ description: "辛さを選べます" })} onItemClickRef={onItemClickRef} />);

        expect(screen.getByText("辛さを選べます")).toBeInTheDocument();
    });

    it("shows option chip when option groups exist", () => {
        const onItemClickRef = createOnItemClickRef();
        render(<MenuItemCard item={createItem()} onItemClickRef={onItemClickRef} />);

        expect(screen.getByText("オプションあり")).toBeInTheDocument();
    });
});
