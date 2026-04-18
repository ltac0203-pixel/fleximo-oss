import { render, screen } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { ReactNode } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";

const usePageMock = vi.hoisted(() => vi.fn());

vi.mock("@inertiajs/react", () => ({
    Link: ({ children, href }: { children?: ReactNode; href?: string }) => <a href={href}>{children}</a>,
    usePage: usePageMock,
}));

vi.mock("@/Components/ApplicationLogo", () => ({
    default: () => <span>logo</span>,
}));

vi.mock("@/Components/GradientBackground", () => ({
    default: () => null,
}));

vi.mock("@/Components/LogoutConfirmModal", () => ({
    default: () => null,
}));

vi.mock("@/Components/NavLink", () => ({
    default: ({ children, href }: { children?: ReactNode; href?: string }) => <a href={href}>{children}</a>,
}));

vi.mock("@/Components/ResponsiveNavLink", () => ({
    default: ({ children, href }: { children?: ReactNode; href?: string }) => <a href={href}>{children}</a>,
}));

vi.mock("@/Components/Dropdown", () => {
    const Dropdown = ({ children }: { children?: ReactNode }) => <div>{children}</div>;
    const Trigger = ({ children }: { children?: ReactNode }) => <>{children}</>;
    const Content = ({ children }: { children?: ReactNode }) => <div>{children}</div>;
    const DropdownLink = ({ children, href }: { children?: ReactNode; href?: string }) => <a href={href}>{children}</a>;

    return {
        default: Object.assign(Dropdown, {
            Trigger,
            Content,
            Link: DropdownLink,
        }),
    };
});

function ThrowFromHeader() {
    throw new Error("header error");
}

function ThrowFromMain() {
    throw new Error("main error");
}

describe("AuthenticatedLayout", () => {
    let consoleErrorSpy: ReturnType<typeof vi.spyOn>;

    beforeEach(() => {
        vi.clearAllMocks();
        consoleErrorSpy = vi.spyOn(console, "error").mockImplementation(() => {});

        usePageMock.mockReturnValue({
            props: {
                auth: {
                    user: {
                        name: "テストユーザー",
                        email: "user@example.com",
                        is_tenant_admin: false,
                    },
                },
            },
        });

        const routeHelper = ((name?: string) => {
            if (typeof name === "undefined") {
                return routeHelper as unknown as string;
            }
            return `/${name}`;
        }) as {
            (name?: string): string;
            current: (name?: string) => boolean;
            has: (name?: string) => boolean;
        };
        routeHelper.current = () => false;
        routeHelper.has = () => true;
        global.route = routeHelper;
    });

    afterEach(() => {
        consoleErrorSpy.mockRestore();
    });

    it("keeps navigation and header when main content throws", () => {
        render(
            <AuthenticatedLayout header={<div>ページヘッダー</div>}>
                <ThrowFromMain />
            </AuthenticatedLayout>,
        );

        expect(screen.getByText("ページヘッダー")).toBeInTheDocument();
        expect(screen.getByText("エラーが発生しました")).toBeInTheDocument();
        expect(screen.getByRole("button", { name: "メニュー" })).toBeInTheDocument();
    });

    it("shows header fallback while keeping main content when header throws", () => {
        render(
            <AuthenticatedLayout header={<ThrowFromHeader />}>
                <div>ページ本文</div>
            </AuthenticatedLayout>,
        );

        expect(screen.getByText("ヘッダーの表示に失敗しました。")).toBeInTheDocument();
        expect(screen.getByText("ページ本文")).toBeInTheDocument();
        expect(screen.queryByText("エラーが発生しました")).not.toBeInTheDocument();
    });
});
