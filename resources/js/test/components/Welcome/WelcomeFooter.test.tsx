import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { ReactNode } from "react";
import WelcomeFooter from "@/Components/Welcome/sections/WelcomeFooter";

vi.mock("@inertiajs/react", () => ({
    Link: ({ children, href }: { children?: ReactNode; href?: string }) => <a href={href}>{children}</a>,
}));

vi.mock("@/Components/ApplicationLogo", () => ({
    default: () => <span>logo</span>,
}));

describe("WelcomeFooter", () => {
    it("renders all service links as active navigation links", () => {
        render(<WelcomeFooter />);

        expect(screen.getByRole("link", { name: "機能一覧" })).toHaveAttribute("href", "/for-business#features");
        expect(screen.getByRole("link", { name: "料金プラン" })).toHaveAttribute("href", "/for-business#pricing");
        expect(screen.getByRole("link", { name: "導入事例" })).toHaveAttribute("href", "/for-business#proof");
        expect(screen.getByRole("link", { name: "よくある質問" })).toHaveAttribute("href", "/for-business#faq");
        expect(screen.queryByText("準備中")).not.toBeInTheDocument();
    });

    it("keeps support links connected to application routes", () => {
        render(<WelcomeFooter />);

        expect(screen.queryByRole("link", { name: "お問い合わせ" })).not.toBeInTheDocument();
        expect(screen.getByRole("link", { name: "事業者様向け" })).toHaveAttribute("href", "/for-business.index");
        expect(screen.getByRole("link", { name: "利用規約" })).toHaveAttribute("href", "/legal.terms");
        expect(screen.getByRole("link", { name: "プライバシーポリシー" })).toHaveAttribute("href", "/legal.privacy-policy");
    });
});
