import { render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import OrderSummary from "@/Components/Customer/Checkout/OrderSummary";
import { Cart } from "@/types";

const cart: Cart = {
    id: 1,
    user_id: 1,
    tenant_id: 1,
    tenant: { id: 1, name: "テスト店舗", slug: "test-shop", is_open: true, today_business_hours: [] },
    items: [
        {
            id: 10,
            menu_item: { id: 100, name: "カレー", description: null, price: 1000, is_sold_out: false },
            quantity: 2,
            options: [{ id: 1, name: "大盛り", price: 100 }],
            subtotal: 2200,
        },
        {
            id: 11,
            menu_item: { id: 101, name: "サラダ", description: null, price: 500, is_sold_out: false },
            quantity: 1,
            options: [],
            subtotal: 500,
        },
    ],
    total: 2700,
    item_count: 3,
    is_empty: false,
};

describe("OrderSummary", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("テナント名が表示される", () => {
        render(<OrderSummary cart={cart} />);
        expect(screen.getByText("テスト店舗")).toBeInTheDocument();
    });

    it("商品名と数量が表示される", () => {
        render(<OrderSummary cart={cart} />);
        expect(screen.getByText("カレー")).toBeInTheDocument();
        expect(screen.getByText("x2")).toBeInTheDocument();
        expect(screen.getByText("サラダ")).toBeInTheDocument();
        expect(screen.getByText("x1")).toBeInTheDocument();
    });

    it("オプションがある場合オプション名が表示される", () => {
        render(<OrderSummary cart={cart} />);
        expect(screen.getByText("大盛り")).toBeInTheDocument();
    });

    it("小計と合計金額が表示される", () => {
        render(<OrderSummary cart={cart} />);
        expect(screen.getByText("小計（3点）")).toBeInTheDocument();
        expect(screen.getByText("合計")).toBeInTheDocument();
        expect(screen.getAllByText("￥2,700").length).toBeGreaterThanOrEqual(1);
    });

    it("「注文内容」ヘッダーが表示される", () => {
        render(<OrderSummary cart={cart} />);
        expect(screen.getByText("注文内容")).toBeInTheDocument();
    });
});
