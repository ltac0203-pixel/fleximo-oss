import { render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import CheckoutIndex from "@/Pages/Customer/Checkout/Index";
import { Cart, CheckoutIndexProps, PaymentMethod, SavedCard } from "@/types";

const useCheckoutMock = vi.hoisted(() => vi.fn());

vi.mock("@/Hooks/useCheckout", () => ({
    useCheckout: useCheckoutMock,
}));

vi.mock("@inertiajs/react", () => ({
    Head: () => null,
    Link: ({ children }: { children?: unknown }) => <>{children}</>,
}));

function createCart(): Cart {
    return {
        id: 1,
        user_id: 1,
        tenant_id: 1,
        tenant: {
            id: 1,
            name: "テスト店舗",
            slug: "test-shop",
        },
        items: [
            {
                id: 10,
                menu_item: {
                    id: 100,
                    name: "カレー",
                    description: null,
                    price: 1000,
                    is_sold_out: false,
                },
                quantity: 1,
                options: [],
                subtotal: 1000,
            },
        ],
        total: 1000,
        item_count: 1,
        is_empty: false,
    };
}

function createSavedCards(): SavedCard[] {
    return [
        {
            id: 11,
            card_no_display: "**** **** **** 4242",
            brand: "VISA",
            expire: "12/30",
            is_default: true,
        },
    ];
}

function createProps(): CheckoutIndexProps {
    return {
        cart: createCart(),
        fincodePublicKey: "pk_test_123",
        isProduction: false,
        savedCards: createSavedCards(),
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
    };
}

function createCheckoutState(
    paymentMethod: PaymentMethod | null,
    savedCardId: number | null,
    saveCard = false,
) {
    return {
        paymentMethod,
        setPaymentMethod: vi.fn(),
        isProcessing: false,
        error: null,
        isCheckoutDisabled: false,
        fincode: {
            isReady: true,
            isLoading: false,
            error: null,
            mountUI: vi.fn(),
            unmountUI: vi.fn(),
        },
        handleCheckout: vi.fn(),
        savedCardId,
        setSavedCardId: vi.fn(),
        saveCard,
        setSaveCard: vi.fn(),
        saveAsDefault: false,
        setSaveAsDefault: vi.fn(),
    };
}

describe("CheckoutIndex", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("shows saved card selector when saved_card is selected and cards exist", () => {
        useCheckoutMock.mockReturnValue(createCheckoutState("saved_card", 11));

        render(<CheckoutIndex {...createProps()} />);

        expect(screen.getByText("カードを選択")).toBeInTheDocument();
    });

    it("shows card form when new_card is selected", () => {
        useCheckoutMock.mockReturnValue(createCheckoutState("new_card", null));
        render(<CheckoutIndex {...createProps()} />);
        expect(screen.getByText("カード情報")).toBeInTheDocument();
    });

    it("does not show card form when saved_card is selected", () => {
        useCheckoutMock.mockReturnValue(createCheckoutState("saved_card", 11));
        render(<CheckoutIndex {...createProps()} />);
        expect(screen.queryByText("カード情報")).not.toBeInTheDocument();
    });

    it("shows save-card checkbox only for new_card", () => {
        const props = createProps();

        useCheckoutMock.mockReturnValue(createCheckoutState("new_card", null));
        const { rerender } = render(<CheckoutIndex {...props} />);
        expect(screen.getByLabelText("このカードを保存する")).toBeInTheDocument();

        useCheckoutMock.mockReturnValue(createCheckoutState("saved_card", 11));
        rerender(<CheckoutIndex {...props} />);
        expect(screen.queryByLabelText("このカードを保存する")).not.toBeInTheDocument();
    });

    it("shows neither card form nor saved card selector when paymentMethod is null", () => {
        useCheckoutMock.mockReturnValue(createCheckoutState(null, null));
        render(<CheckoutIndex {...createProps()} />);
        expect(screen.queryByText("カード情報")).not.toBeInTheDocument();
        expect(screen.queryByText("カードを選択")).not.toBeInTheDocument();
    });

    it("shows default-card checkbox only when save-card is enabled", () => {
        const props = createProps();

        useCheckoutMock.mockReturnValue(createCheckoutState("new_card", null, false));
        const { rerender } = render(<CheckoutIndex {...props} />);
        expect(screen.queryByLabelText("このカードをメインにする")).not.toBeInTheDocument();

        useCheckoutMock.mockReturnValue(createCheckoutState("new_card", null, true));
        rerender(<CheckoutIndex {...props} />);
        expect(screen.getByLabelText("このカードをメインにする")).toBeInTheDocument();
    });
});
