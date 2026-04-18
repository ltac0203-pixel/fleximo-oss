import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import PrimaryButton from "@/Components/PrimaryButton";

describe("PrimaryButton", () => {
    it("does not show a busy indicator when not busy", () => {
        render(<PrimaryButton>送信</PrimaryButton>);

        const button = screen.getByRole("button", { name: /送信/ });

        expect(button).not.toHaveAttribute("aria-busy");
        expect(button).not.toBeDisabled();
        expect(button.querySelector("span[aria-hidden='true']")).toBeNull();
    });

    it("shows spinner and aria-busy when busy", () => {
        render(<PrimaryButton isBusy>送信</PrimaryButton>);

        const button = screen.getByRole("button", { name: /処理中/ });

        expect(button).toHaveAttribute("aria-busy", "true");
        expect(button).toBeDisabled();
        expect(button.querySelector("span[aria-hidden='true']")).not.toBeNull();
        expect(screen.getByText("処理中")).toBeInTheDocument();
        expect(screen.queryByText("送信")).not.toBeInTheDocument();
    });

    it("stays non-busy when only disabled", () => {
        render(<PrimaryButton disabled>送信</PrimaryButton>);

        const button = screen.getByRole("button", { name: /送信/ });

        expect(button).not.toHaveAttribute("aria-busy");
        expect(button).toBeDisabled();
        expect(button.querySelector("span[aria-hidden='true']")).toBeNull();
    });

    it("forces disabled state while busy even when disabled is false", () => {
        render(
            <PrimaryButton disabled={false} isBusy>
                送信
            </PrimaryButton>,
        );

        const button = screen.getByRole("button", { name: /処理中/ });

        expect(button).toHaveAttribute("aria-busy", "true");
        expect(button).toBeDisabled();
    });
});
