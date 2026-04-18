import React from "react";
import { render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import CartSummary from "@/Components/Customer/Cart/CartSummary";

vi.mock("@inertiajs/react", () => ({
    Link: ({ children, href, ...props }: React.PropsWithChildren<React.AnchorHTMLAttributes<HTMLAnchorElement>>) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
}));

describe("CartSummary", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("合計金額が表示される", () => {
        render(<CartSummary grandTotal={2700} itemCount={3} />);
        expect(screen.getAllByText("￥2,700").length).toBeGreaterThanOrEqual(1);
    });

    it("商品点数が「合計（N点）」形式で表示される", () => {
        render(<CartSummary grandTotal={2700} itemCount={3} />);
        expect(screen.getByText("合計（3点）")).toBeInTheDocument();
    });

    it("「注文手続きへ」テキストが表示される", () => {
        render(<CartSummary grandTotal={2700} itemCount={3} />);
        expect(screen.getByText("注文手続きへ")).toBeInTheDocument();
    });

    it("checkoutDisabled=true のときボタンが disabled スタイルになる", () => {
        render(<CartSummary grandTotal={2700} itemCount={3} checkoutDisabled={true} />);
        const button = screen.getByRole("button");
        expect(button).toBeDisabled();
    });

    it("checkoutUrl が指定されたとき Link（a タグ）でレンダリングされる", () => {
        render(<CartSummary grandTotal={2700} itemCount={3} checkoutUrl="/checkout" />);
        const link = screen.getByRole("link");
        expect(link).toHaveAttribute("href", "/checkout");
        expect(link).toHaveTextContent("注文手続きへ");
    });
});
