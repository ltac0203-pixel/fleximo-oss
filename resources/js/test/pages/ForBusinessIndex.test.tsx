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
        expect(screen.getAllByRole("link", { name: "お問い合わせ" })[0]).toHaveAttribute(
            "href",
            "/contact.index",
        );
    });

    it("shows supported payment brands in the proof section", () => {
        render(<ForBusiness {...createProps()} />);

        expect(screen.getByRole("heading", { name: "導入を支える安心の決済実績" })).toBeInTheDocument();
        expect(screen.getByText("Diners Club")).toBeInTheDocument();
        expect(screen.getByText("Discover")).toBeInTheDocument();
    });
});
