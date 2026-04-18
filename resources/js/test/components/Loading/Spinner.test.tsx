import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import Spinner from "@/Components/Loading/Spinner";

describe("Spinner", () => {
    it("renders with default props", () => {
        render(<Spinner />);
        const spinner = screen.getByRole("status");
        expect(spinner).toBeInTheDocument();
        expect(spinner).toHaveAttribute("aria-label", "読み込み中");
        expect(spinner).toHaveClass("h-8", "w-8");
        expect(spinner).toHaveClass("border-slate-200", "border-t-sky-500");
    });

    it("renders sm size", () => {
        render(<Spinner size="sm" />);
        expect(screen.getByRole("status")).toHaveClass("h-5", "w-5");
    });

    it("renders lg size", () => {
        render(<Spinner size="lg" />);
        expect(screen.getByRole("status")).toHaveClass("h-12", "w-12");
    });

    it("renders white variant", () => {
        render(<Spinner variant="white" />);
        const spinner = screen.getByRole("status");
        expect(spinner).toHaveClass("border-white/40", "border-t-white");
    });

    it("renders muted variant", () => {
        render(<Spinner variant="muted" />);
        const spinner = screen.getByRole("status");
        expect(spinner).toHaveClass("border-edge-strong", "border-t-primary-dark");
    });

    it("uses custom label", () => {
        render(<Spinner label="処理中" />);
        expect(screen.getByRole("status")).toHaveAttribute("aria-label", "処理中");
        expect(screen.getByText("処理中")).toHaveClass("sr-only");
    });

    it("applies additional className", () => {
        render(<Spinner className="mt-4" />);
        expect(screen.getByRole("status")).toHaveClass("mt-4");
    });
});
