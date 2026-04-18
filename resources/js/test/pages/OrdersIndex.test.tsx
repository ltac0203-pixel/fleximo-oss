import React from "react";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import OrdersIndex from "@/Pages/Customer/Orders/Index";
import { OrderListItem, OrdersIndexPageProps } from "@/types";

const useReorderMock = vi.hoisted(() => vi.fn());
const routerGetMock = vi.hoisted(() => vi.fn());

vi.mock("@/Hooks/useReorder", () => ({
    useReorder: useReorderMock,
}));

vi.mock("@/Components/GradientBackground", () => ({
    default: () => null,
}));

vi.mock("@/Components/Customer/Orders/ReorderResultModal", () => ({
    default: ({ result }: { result: unknown }) => (result ? <div data-testid="reorder-result-modal" /> : null),
}));

vi.mock("@inertiajs/react", () => ({
    Head: () => null,
    Link: ({ children, href, ...props }: React.PropsWithChildren<React.AnchorHTMLAttributes<HTMLAnchorElement>>) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
    router: {
        get: routerGetMock,
    },
}));

function createOrder(id: number): OrderListItem {
    return {
        id,
        order_code: `A-${id}`,
        tenant: { id: 10, name: "テスト店舗" },
        status: "completed",
        status_label: "完了",
        total_amount: 1200,
        created_at: "2026-02-20T10:00:00+09:00",
    };
}

function createProps(data: OrderListItem[]): OrdersIndexPageProps {
    return {
        orders: {
            data,
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: data.length,
            links: [],
            from: data.length > 0 ? 1 : null,
            to: data.length > 0 ? data.length : null,
        },
    } as OrdersIndexPageProps;
}

function createReorderState(overrides: Partial<ReturnType<typeof useReorderMock>> = {}) {
    return {
        reorder: vi.fn().mockResolvedValue(undefined),
        isLoading: false,
        error: null,
        result: null,
        clearResult: vi.fn(),
        ...overrides,
    };
}

describe("OrdersIndex", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("カード内の再注文ボタン押下でreorderが呼ばれる", async () => {
        const reorder = vi.fn().mockResolvedValue(undefined);
        useReorderMock.mockReturnValue(createReorderState({ reorder }));

        render(<OrdersIndex {...createProps([createOrder(1)])} />);

        fireEvent.click(screen.getByRole("button", { name: "もう一度注文する" }));

        await waitFor(() => {
            expect(reorder).toHaveBeenCalledTimes(1);
            expect(reorder).toHaveBeenCalledWith(1);
        });
    });

    it("reorderエラーがある場合はエラーメッセージを表示する", () => {
        useReorderMock.mockReturnValue(
            createReorderState({
                error: "再注文に失敗しました。",
            }),
        );

        render(<OrdersIndex {...createProps([createOrder(1)])} />);

        expect(screen.getByText("再注文に失敗しました。")).toBeInTheDocument();
    });

    it("reorder結果がある場合は結果モーダルを表示する", () => {
        useReorderMock.mockReturnValue(
            createReorderState({
                result: { summary: { items_added: 1 } },
            }),
        );

        render(<OrdersIndex {...createProps([createOrder(1)])} />);

        expect(screen.getByTestId("reorder-result-modal")).toBeInTheDocument();
    });

    it("ローディング中は押下したカードのボタンのみ無効化される", async () => {
        const reorder = vi.fn(() => new Promise<void>(() => {}));
        useReorderMock.mockReturnValue(
            createReorderState({
                reorder,
                isLoading: true,
            }),
        );

        render(<OrdersIndex {...createProps([createOrder(1), createOrder(2)])} />);

        fireEvent.click(screen.getAllByRole("button", { name: "もう一度注文する" })[0]);

        await waitFor(() => {
            const buttons = screen.getAllByRole("button");
            expect(buttons).toHaveLength(2);
            expect(buttons[0]).toBeDisabled();
            expect(buttons[1]).not.toBeDisabled();
        });
    });
});
