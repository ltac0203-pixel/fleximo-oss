import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { beforeEach, describe, expect, it, vi } from "vitest";
import CardsIndex from "@/Pages/Customer/Cards/Index";

const registerCardMock = vi.hoisted(() => vi.fn());
const useCardManagementState = vi.hoisted(() => ({
    successMessage: null as string | null,
}));
const routeMock = vi.hoisted(() => vi.fn(() => "/mock"));

vi.mock("@/Components/Customer/Cards/CardRegistrationForm", () => ({
    default: ({
        isDefault,
        onDefaultChange,
        onSubmit,
    }: {
        isDefault: boolean;
        onDefaultChange: (checked: boolean) => void;
        onSubmit: () => void;
    }) => (
        <div>
            <label>
                <input
                    type="checkbox"
                    checked={isDefault}
                    onChange={(event) => onDefaultChange(event.target.checked)}
                />
                このカードをメインカードに設定する
            </label>
            <button type="button" onClick={onSubmit}>
                register-card
            </button>
        </div>
    ),
}));

vi.mock("@/Components/Customer/Cards/SavedCardList", () => ({
    default: () => null,
}));

vi.mock("@/Components/ConfirmModal", () => ({
    default: () => null,
}));

vi.mock("@/Hooks/useFincode", () => ({
    useFincode: () => ({
        isReady: false,
        isLoading: false,
        error: null,
        mountUI: vi.fn(),
        unmountUI: vi.fn(),
        createToken: vi.fn(),
        clearForm: vi.fn(),
    }),
}));

vi.mock("@/Hooks/useCardManagement", () => ({
    useCardManagement: () => ({
        cards: [],
        registerCard: registerCardMock,
        deleteCard: vi.fn(),
        isRegistering: false,
        deletingId: null,
        error: null,
        successMessage: useCardManagementState.successMessage,
    }),
}));

vi.mock("@inertiajs/react", () => ({
    Head: () => null,
    Link: ({ children, href }: React.PropsWithChildren<{ href: string }>) => <a href={href}>{children}</a>,
}));

describe("CardsIndex", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        useCardManagementState.successMessage = null;
        vi.stubGlobal("route", routeMock);
    });

    it("submits the current default-card selection from page state", async () => {
        const user = userEvent.setup();

        const props: Parameters<typeof CardsIndex>[0] = {
            tenant: {
                id: 1,
                name: "テスト店舗",
                slug: "test-shop",
            },
            cards: [],
            fincodePublicKey: "pk_test_123",
            isProduction: false,
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

        render(<CardsIndex {...props} />);

        const checkbox = screen.getByLabelText("このカードをメインカードに設定する");
        await user.click(checkbox);
        await user.click(screen.getByRole("button", { name: "register-card" }));

        expect(registerCardMock).toHaveBeenCalledWith({ isDefault: false });
    });

    it("resets the default-card checkbox after a successful registration message", async () => {
        const user = userEvent.setup();

        const props: Parameters<typeof CardsIndex>[0] = {
            tenant: {
                id: 1,
                name: "テスト店舗",
                slug: "test-shop",
            },
            cards: [],
            fincodePublicKey: "pk_test_123",
            isProduction: false,
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

        const { rerender } = render(<CardsIndex {...props} />);

        const checkbox = screen.getByLabelText("このカードをメインカードに設定する");
        await user.click(checkbox);
        expect(checkbox).not.toBeChecked();

        useCardManagementState.successMessage = "カードを登録しました";
        rerender(<CardsIndex {...props} />);

        expect(screen.getByLabelText("このカードをメインカードに設定する")).toBeChecked();
    });
});
