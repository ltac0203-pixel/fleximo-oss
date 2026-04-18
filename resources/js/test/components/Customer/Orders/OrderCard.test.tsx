import React from "react";
import { fireEvent, render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import OrderCard from "@/Components/Customer/Orders/OrderCard";
import { OrderListItem, OrderStatusValue } from "@/types";

vi.mock("@inertiajs/react", () => ({
    Link: ({ children, href, ...props }: React.PropsWithChildren<React.AnchorHTMLAttributes<HTMLAnchorElement>>) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
}));

function createOrder(status: OrderStatusValue = "completed", id = 1): OrderListItem {
    return {
        id,
        order_code: `A${id}`,
        tenant: {
            id: 10,
            name: "テスト店舗",
        },
        status,
        status_label: "完了",
        total_amount: 1200,
        created_at: "2026-02-20T10:00:00+09:00",
    };
}

describe("OrderCard", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("completed注文では再注文ボタンが表示される", () => {
        render(<OrderCard order={createOrder("completed")} onReorder={vi.fn()} />);
        expect(screen.getByRole("button", { name: "もう一度注文する" })).toBeInTheDocument();
    });

    it("completed以外の注文では再注文ボタンが表示されない", () => {
        render(<OrderCard order={createOrder("ready")} onReorder={vi.fn()} />);
        expect(screen.queryByRole("button", { name: "もう一度注文する" })).not.toBeInTheDocument();
    });

    it("再注文ボタン押下時にonReorderへ注文IDを渡す", () => {
        const order = createOrder("completed", 42);
        const onReorder = vi.fn();

        render(<OrderCard order={order} onReorder={onReorder} />);
        fireEvent.click(screen.getByRole("button", { name: "もう一度注文する" }));

        expect(onReorder).toHaveBeenCalledTimes(1);
        expect(onReorder).toHaveBeenCalledWith(42);
    });

    it("ローディング対象の注文IDでは再注文ボタンが無効化される", () => {
        const order = createOrder("completed", 7);

        render(<OrderCard order={order} onReorder={vi.fn()} reorderLoadingOrderId={7} />);

        const button = screen.getByRole("button");
        expect(button).toBeDisabled();
        expect(button).toHaveAttribute("aria-busy", "true");
    });
});
