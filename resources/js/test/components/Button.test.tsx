import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import Button from "@/Components/UI/Button";

describe("Button", () => {
    describe("variant=primary", () => {
        it("does not show a busy indicator when not busy", () => {
            render(<Button>送信</Button>);

            const button = screen.getByRole("button", { name: /送信/ });

            expect(button).not.toHaveAttribute("aria-busy");
            expect(button).not.toBeDisabled();
            expect(button.querySelector("span[aria-hidden='true']")).toBeNull();
        });

        it("shows spinner and aria-busy when busy", () => {
            render(<Button isBusy>送信</Button>);

            const button = screen.getByRole("button", { name: /処理中/ });

            expect(button).toHaveAttribute("aria-busy", "true");
            expect(button).toBeDisabled();
            expect(button.querySelector("span[aria-hidden='true']")).not.toBeNull();
            expect(screen.getByText("処理中")).toBeInTheDocument();
            expect(screen.queryByText("送信")).not.toBeInTheDocument();
        });

        it("stays non-busy when only disabled", () => {
            render(<Button disabled>送信</Button>);

            const button = screen.getByRole("button", { name: /送信/ });

            expect(button).not.toHaveAttribute("aria-busy");
            expect(button).toBeDisabled();
            expect(button.querySelector("span[aria-hidden='true']")).toBeNull();
        });

        it("forces disabled state while busy even when disabled is false", () => {
            render(
                <Button disabled={false} isBusy>
                    送信
                </Button>,
            );

            const button = screen.getByRole("button", { name: /処理中/ });

            expect(button).toHaveAttribute("aria-busy", "true");
            expect(button).toBeDisabled();
        });
    });

    describe("variant=danger", () => {
        it("does not show a busy indicator when not busy", () => {
            render(<Button variant="danger">削除</Button>);

            const button = screen.getByRole("button", { name: /削除/ });

            expect(button).not.toHaveAttribute("aria-busy");
            expect(button).not.toBeDisabled();
            expect(button.querySelector("span[aria-hidden='true']")).toBeNull();
        });

        it("shows spinner and aria-busy when busy", () => {
            render(
                <Button variant="danger" isBusy>
                    削除
                </Button>,
            );

            const button = screen.getByRole("button", { name: /処理中/ });

            expect(button).toHaveAttribute("aria-busy", "true");
            expect(button).toBeDisabled();
            expect(button.querySelector("span[aria-hidden='true']")).not.toBeNull();
            expect(screen.getByText("処理中")).toBeInTheDocument();
            expect(screen.queryByText("削除")).not.toBeInTheDocument();
        });

        it("stays non-busy when only disabled", () => {
            render(
                <Button variant="danger" disabled>
                    削除
                </Button>,
            );

            const button = screen.getByRole("button", { name: /削除/ });

            expect(button).not.toHaveAttribute("aria-busy");
            expect(button).toBeDisabled();
            expect(button.querySelector("span[aria-hidden='true']")).toBeNull();
        });
    });

    describe("variant=secondary", () => {
        it("ignores isBusy (no spinner, no aria-busy)", () => {
            render(
                <Button variant="secondary" isBusy>
                    キャンセル
                </Button>,
            );

            const button = screen.getByRole("button", { name: /キャンセル/ });

            expect(button).not.toHaveAttribute("aria-busy");
            expect(button).not.toBeDisabled();
            expect(button.querySelector("span[aria-hidden='true']")).toBeNull();
            expect(screen.getByText("キャンセル")).toBeInTheDocument();
        });

        it("respects disabled prop", () => {
            render(
                <Button variant="secondary" disabled>
                    キャンセル
                </Button>,
            );

            const button = screen.getByRole("button", { name: /キャンセル/ });

            expect(button).toBeDisabled();
        });
    });
});
