import { render, screen } from "@testing-library/react";
import type { AnchorHTMLAttributes, ComponentProps, PropsWithChildren, ReactNode } from "react";
import { describe, expect, it, vi } from "vitest";
import Welcome from "@/Pages/Welcome";

vi.mock("@inertiajs/react", () => ({
    Link: ({
        children,
        href,
        ...props
    }: PropsWithChildren<AnchorHTMLAttributes<HTMLAnchorElement>>) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
    Head: ({ children }: { children?: ReactNode }) => <>{children}</>,
    usePage: () => ({
        url: "/",
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
}));

vi.mock("@/Components/ApplicationLogo", () => ({
    default: () => <span>logo</span>,
}));

vi.mock("@/Components/GradientBackground", () => ({
    default: () => null,
}));

vi.mock("@/Components/SeoHead", () => ({
    default: () => null,
}));

type WelcomeProps = ComponentProps<typeof Welcome>;

function createProps(overrides: Partial<WelcomeProps> = {}): WelcomeProps {
    return {
        auth: {
            user: undefined,
        },
        flash: {
            success: null,
            error: null,
        },
        seo: {
            title: "Fleximo",
            description: "学食向けモバイルオーダー",
        },
        structuredData: [],
        ...overrides,
    } as WelcomeProps;
}

describe("Welcome page", () => {
    it("shows the public LP with register CTAs for guests", () => {
        render(<Welcome {...createProps()} />);

        expect(screen.getByRole("heading", { name: /昼休みの行列、/ })).toBeInTheDocument();
        expect(screen.getByText("忙しい昼休みに必要な情報だけを、すばやく。")).toBeInTheDocument();

        const registerLinks = screen.getAllByRole("link", { name: "無料で会員登録" });
        expect(registerLinks).toHaveLength(2);
        registerLinks.forEach((link) => {
            expect(link).toHaveAttribute("href", "/register");
        });

        expect(screen.getByRole("link", { name: "事業者の方はこちら" })).toHaveAttribute(
            "href",
            "/for-business.index",
        );
    });

    it("switches the primary CTA to dashboard for logged-in users", () => {
        render(
            <Welcome
                {...createProps({
                    auth: {
                        user: {
                            id: 1,
                            name: "山田",
                            email: "yamada@example.com",
                            role: "customer",
                        },
                    },
                })}
            />,
        );

        expect(screen.getByText(/おかえりなさい、/)).toBeInTheDocument();

        const dashboardLinks = screen.getAllByRole("link", { name: "ダッシュボードへ" });
        expect(dashboardLinks).toHaveLength(2);
        dashboardLinks.forEach((link) => {
            expect(link).toHaveAttribute("href", "/dashboard");
        });

        expect(screen.queryByText("ログインはこちら")).not.toBeInTheDocument();
        expect(screen.queryByText("ログイン")).not.toBeInTheDocument();
    });
});
