import { render, screen } from "@testing-library/react";
import type { AnchorHTMLAttributes, ComponentProps, PropsWithChildren, ReactNode } from "react";
import { describe, expect, it, vi } from "vitest";
import ForBusiness from "@/Pages/ForBusiness/Index";

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
        url: "/for-business",
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

type ForBusinessProps = ComponentProps<typeof ForBusiness>;

function createProps(overrides: Partial<ForBusinessProps> = {}): ForBusinessProps {
    return {
        auth: {
            user: undefined,
        },
        flash: {
            success: null,
            error: null,
        },
        seo: {
            title: "Fleximo for Business",
            description: "飲食店向けモバイルオーダー",
        },
        structuredData: [],
        ...overrides,
    } as ForBusinessProps;
}

describe("ForBusiness page", () => {
    it("keeps the main business CTAs and key sections visible", () => {
        render(<ForBusiness {...createProps()} />);

        expect(
            screen.getByRole("heading", {
                name: /毎日の行列が、\s*売上機会の損失\s*になっていませんか？/,
            }),
        ).toBeInTheDocument();
        expect(screen.getByText("Fleximoの主要機能")).toBeInTheDocument();
        expect(screen.getByRole("heading", { name: "料金プラン" })).toBeInTheDocument();

        const applicationLinks = screen.getAllByRole("link", { name: "無料でテナント申請" });
        expect(applicationLinks).toHaveLength(3);
        applicationLinks.forEach((link) => {
            expect(link).toHaveAttribute("href", "/tenant-application.create");
        });

        expect(screen.getByRole("link", { name: "事業者ログイン" })).toHaveAttribute(
            "href",
            "/for-business.login",
        );
        expect(screen.queryByRole("link", { name: "お問い合わせ" })).not.toBeInTheDocument();
    });

    it("shows supported payment brands in the proof section", () => {
        render(<ForBusiness {...createProps()} />);

        expect(screen.getByRole("heading", { name: "導入を支える安心の決済実績" })).toBeInTheDocument();
        expect(screen.getByText("Diners Club")).toBeInTheDocument();
        expect(screen.getByText("Discover")).toBeInTheDocument();
    });
});
