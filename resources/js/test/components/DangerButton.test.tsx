import DangerButton from "@/Components/DangerButton";
import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";

describe("DangerButton", () => {
    it("does not show a busy indicator when not busy", () => {
        render(<DangerButton>削除</DangerButton>);

        const button = screen.getByRole("button", { name: /削除/ });

        expect(button).not.toHaveAttribute("aria-busy");
        expect(button).not.toBeDisabled();
        expect(button.querySelector("span[aria-hidden='true']")).toBeNull();
    });

    it("shows spinner only and aria-busy when busy", () => {
        render(<DangerButton isBusy>削除</DangerButton>);

        const button = screen.getByRole("button", { name: /処理中/ });

        expect(button).toHaveAttribute("aria-busy", "true");
        expect(button).toBeDisabled();
        expect(button.querySelector("span[aria-hidden='true']")).not.toBeNull();
        expect(screen.getByText("処理中")).toBeInTheDocument();
        expect(screen.queryByText("削除")).not.toBeInTheDocument();
    });

    it("stays non-busy when only disabled", () => {
        render(<DangerButton disabled>削除</DangerButton>);

        const button = screen.getByRole("button", { name: /削除/ });

        expect(button).not.toHaveAttribute("aria-busy");
        expect(button).toBeDisabled();
        expect(button.querySelector("span[aria-hidden='true']")).toBeNull();
    });
});
