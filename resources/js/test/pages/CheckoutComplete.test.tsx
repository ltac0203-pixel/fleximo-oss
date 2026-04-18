import { render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import CheckoutComplete from "@/Pages/Customer/Checkout/Complete";
import { CheckoutCompleteProps, OrderStatusValue } from "@/types";

const pollingStateMock = vi.hoisted(() => ({
    value: {
        status: "in_progress" as OrderStatusValue,
        statusLabel: "調理中",
        isReady: false,
        isTerminal: false,
        readyAt: null as string | null,
    },
}));

vi.mock("@inertiajs/react", () => ({
    Head: () => null,
    Link: ({ children, href, className }: { children?: unknown; href: string; className?: string }) => (
        <a href={href} className={className}>
            {children}
        </a>
    ),
}));

vi.mock("@/Components/GradientBackground", () => ({
    default: () => null,
}));

vi.mock("@/Components/Customer/Orders/OrderItemList", () => ({
    default: () => <div>items</div>,
}));

vi.mock("@/Components/Customer/Orders/OrderTimeline", () => ({
    default: () => <div>timeline</div>,
}));

vi.mock("@/Components/Customer/Orders/OrderReadyNotifier", () => ({
    default: ({ children }: { children: (polling: typeof pollingStateMock.value) => unknown }) => (
        <>{children(pollingStateMock.value)}</>
    ),
}));

function setPollingState(status: OrderStatusValue, statusLabel: string, isReady: boolean) {
    pollingStateMock.value = {
        status,
        statusLabel,
        isReady,
        isTerminal: false,
        readyAt: null,
    };
}

function createProps(overrides?: Partial<CheckoutCompleteProps>): CheckoutCompleteProps {
    return {
        auth: {
            user: {
                id: 1,
                name: "テストユーザー",
                email: "user@example.com",
                role: "customer",
            },
        },
        flash: {
            success: null,
            error: null,
        },
        order: {
            id: 1,
            order_code: "A12345",
            business_date: "2026-02-28",
            tenant: {
                id: 10,
                name: "テスト店舗",
                slug: "test-shop",
                address: "東京都渋谷区1-2-3",
            },
            status: "in_progress",
            status_label: "調理中",
            can_be_cancelled: true,
            total_amount: 1200,
            items: [],
            payment: {
                method: "card",
                method_label: "クレジットカード",
                status: "completed",
                status_label: "完了",
            },
            paid_at: "2026-02-28T10:01:00+09:00",
            accepted_at: "2026-02-28T10:02:00+09:00",
            in_progress_at: "2026-02-28T10:03:00+09:00",
            ready_at: null,
            completed_at: null,
            cancelled_at: null,
            created_at: "2026-02-28T10:00:00+09:00",
        },
        ...overrides,
    };
}

describe("CheckoutComplete", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        setPollingState("in_progress", "調理中", false);
    });

    it("shows current status and next action for in_progress", () => {
        render(<CheckoutComplete {...createProps()} />);

        expect(screen.getByText("現在の注文状況")).toBeInTheDocument();
        expect(screen.getByText("現在、商品を準備中です")).toBeInTheDocument();
        expect(screen.getByText("準備ができ次第、この画面でお知らせします。")).toBeInTheDocument();
    });

    it("shows ready-specific summary when status is ready", () => {
        setPollingState("ready", "準備完了", true);

        render(<CheckoutComplete {...createProps()} />);

        expect(screen.getByText("現在、商品の準備ができています")).toBeInTheDocument();
        expect(screen.getByText("カウンターで注文番号をお伝えください。")).toBeInTheDocument();
    });

    it("shows failure summary when status is payment_failed", () => {
        setPollingState("payment_failed", "決済失敗", false);

        render(
            <CheckoutComplete
                {...createProps({
                    order: {
                        ...createProps().order,
                        status: "payment_failed",
                        status_label: "決済失敗",
                    },
                })}
            />,
        );

        expect(screen.getByText("決済に失敗しました")).toBeInTheDocument();
        expect(screen.getByText("支払い方法を確認のうえ、再度お試しください。")).toBeInTheDocument();
    });

    it("renders order number with emphasized size", () => {
        render(<CheckoutComplete {...createProps()} />);

        const orderCode = screen.getByText("#A12345");
        expect(orderCode).toHaveClass("text-4xl");
    });
});
