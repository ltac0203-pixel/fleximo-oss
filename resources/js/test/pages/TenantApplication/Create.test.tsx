import Create from "@/Pages/TenantApplication/Create";
import {
    BusinessTypeOption,
    TenantApplicationFormData,
    TenantApplicationFormErrors,
    TenantApplicationFormField,
} from "@/Pages/TenantApplication/types";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

const postMock = vi.hoisted(() => vi.fn());
const mockInertiaErrors = vi.hoisted(() => ({ current: {} as TenantApplicationFormErrors }));

vi.mock("@/Layouts/WideGuestLayout", () => ({
    default: ({ children }: React.PropsWithChildren) => <div>{children}</div>,
}));

vi.mock("@inertiajs/react", () => ({
    Head: () => null,
    Link: ({ children, href, ...props }: React.PropsWithChildren<React.AnchorHTMLAttributes<HTMLAnchorElement>>) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
    usePage: () => ({
        url: "/tenant-application/create",
        props: {
            siteConfig: {
                name: "Fleximo",
                baseUrl: "http://localhost",
                contactEmail: "contact@example.com",
                supportEmail: "support@example.com",
                logoUrl: "/logo.png",
                defaultImageUrl: "/og-default.png",
            },
        },
    }),
    useForm: (initialData: TenantApplicationFormData) => {
        const [data, setDataState] = React.useState(initialData);

        const setData = (key: TenantApplicationFormField, value: string) => {
            setDataState((previous) => ({
                ...previous,
                [key]: value,
            }));
        };

        return {
            data,
            setData,
            post: postMock,
            processing: false,
            errors: mockInertiaErrors.current,
        };
    },
}));

const businessTypes: BusinessTypeOption[] = [
    { value: "restaurant", label: "飲食店" },
    { value: "cafe", label: "カフェ" },
];

async function fillValidStep1Form() {
    const user = userEvent.setup();
    await user.type(screen.getByLabelText("店舗名"), "テスト店舗");
    await user.selectOptions(screen.getByLabelText("業種"), "restaurant");
    await user.type(screen.getByLabelText("住所（任意）"), "東京都渋谷区1-2-3");
    await user.type(screen.getByLabelText("お名前"), "山田 太郎");
    await user.type(screen.getByLabelText("電話番号"), "09012345678");
    await user.type(screen.getByLabelText("メールアドレス"), "taro@example.com");
    await user.type(screen.getByLabelText("パスワード"), "password123");
    await user.type(screen.getByLabelText("パスワード（確認）"), "password123");
}

describe("TenantApplication Create page", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockInertiaErrors.current = {};
        vi.spyOn(window, "scrollTo").mockImplementation(() => undefined);
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it("shows step 1 by default", () => {
        render(<Create businessTypes={businessTypes} />);

        expect(screen.getByRole("heading", { name: "店舗情報" })).toBeInTheDocument();
        expect(screen.queryByRole("heading", { name: "入力内容の確認" })).not.toBeInTheDocument();
    });

    it("stays on step 1 and focuses first invalid field when clicking next with empty form", async () => {
        const user = userEvent.setup();

        render(<Create businessTypes={businessTypes} />);

        const nextButton = screen.getByRole("button", { name: "次へ" });
        nextButton.focus();
        await user.click(nextButton);

        expect(screen.getByText("店舗名を入力してください")).toBeInTheDocument();
        expect(screen.queryByRole("heading", { name: "入力内容の確認" })).not.toBeInTheDocument();
        expect(document.activeElement).toHaveAttribute("id", "tenant_name");
    });

    it("moves to step 2 when step 1 is valid", async () => {
        const user = userEvent.setup();

        render(<Create businessTypes={businessTypes} />);

        await fillValidStep1Form();
        await user.click(screen.getByRole("button", { name: "次へ" }));

        expect(screen.getByRole("heading", { name: "入力内容の確認" })).toBeInTheDocument();
    });

    it("returns to step 1 when clicking back from step 2", async () => {
        const user = userEvent.setup();

        render(<Create businessTypes={businessTypes} />);

        await fillValidStep1Form();
        await user.click(screen.getByRole("button", { name: "次へ" }));
        await user.click(screen.getByRole("button", { name: "修正する" }));

        expect(screen.getByRole("heading", { name: "店舗情報" })).toBeInTheDocument();
    });

    it("returns to step 1 when server errors appear on step 2", async () => {
        const user = userEvent.setup();

        const { rerender } = render(<Create businessTypes={businessTypes} />);

        await fillValidStep1Form();
        await user.click(screen.getByRole("button", { name: "次へ" }));
        expect(screen.getByRole("heading", { name: "入力内容の確認" })).toBeInTheDocument();

        mockInertiaErrors.current = { applicant_email: "このメールアドレスは既に登録されています" };
        rerender(<Create businessTypes={businessTypes} />);

        await waitFor(() => {
            expect(screen.getByRole("heading", { name: "店舗情報" })).toBeInTheDocument();
        });
        expect(screen.getByText("このメールアドレスは既に登録されています")).toBeInTheDocument();
    });
});
