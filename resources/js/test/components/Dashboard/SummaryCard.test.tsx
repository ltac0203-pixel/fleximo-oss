import { render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import SummaryCard from "@/Components/Dashboard/SummaryCard";

describe("SummaryCard", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("タイトルと値が表示される", () => {
        render(<SummaryCard title="本日の売上" value={50000} />);
        expect(screen.getByText("本日の売上")).toBeInTheDocument();
    });

    it("value が number のとき formatPrice でフォーマットされる", () => {
        render(<SummaryCard title="売上" value={50000} />);
        expect(screen.getByText("￥50,000")).toBeInTheDocument();
    });

    it("value が string のときそのまま表示される", () => {
        render(<SummaryCard title="注文数" value="25件" />);
        expect(screen.getByText("25件")).toBeInTheDocument();
    });

    it("change が null のとき '--' が表示される", () => {
        render(<SummaryCard title="売上" value={50000} change={null} />);
        expect(screen.getByText("--")).toBeInTheDocument();
    });

    it("change > 0 のとき上矢印と緑色テキストが表示される", () => {
        render(<SummaryCard title="売上" value={50000} change={11.1} />);
        const changeEl = screen.getByText(/↑/);
        expect(changeEl).toBeInTheDocument();
        expect(changeEl).toHaveClass("text-green-600");
    });

    it("change < 0 のとき下矢印と赤色テキストが表示される", () => {
        render(<SummaryCard title="売上" value={50000} change={-5.2} />);
        const changeEl = screen.getByText(/↓/);
        expect(changeEl).toBeInTheDocument();
        expect(changeEl).toHaveClass("text-red-600");
    });

    it("change === 0 のとき灰色テキストで矢印なし", () => {
        render(<SummaryCard title="売上" value={50000} change={0} />);
        const changeEl = screen.getByText(/0%/);
        expect(changeEl).toBeInTheDocument();
        expect(changeEl).toHaveClass("text-muted");
        expect(changeEl.textContent).not.toMatch(/[↑↓]/);
    });
});
