import { render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import SummaryCards from "@/Components/Dashboard/SummaryCards";
import { DashboardSummary } from "@/types";

const mockSummary: DashboardSummary = {
    today: { sales: 50000, orders: 25, average: 2000 },
    yesterday: { sales: 45000, orders: 20, average: 2250 },
    this_month: { sales: 1500000, orders: 750, average: 2000 },
    last_month: { sales: 1400000, orders: 700, average: 2000 },
    comparison: {
        daily_change: { sales_percent: 11.1, orders_percent: 25.0 },
        monthly_change: { sales_percent: 7.1, orders_percent: 7.1 },
    },
};

describe("SummaryCards", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("summary が null のときスケルトンが表示される", () => {
        render(<SummaryCards summary={null as unknown as DashboardSummary} />);
        const skeletons = screen.getAllByTestId("summary-skeleton");
        expect(skeletons.length).toBeGreaterThanOrEqual(4);
    });

    it("summary.today が null のときスケルトンが表示される", () => {
        const incompleteSummary = {
            ...mockSummary,
            today: null,
        } as unknown as DashboardSummary;
        render(<SummaryCards summary={incompleteSummary} />);
        const skeletons = screen.getAllByTestId("summary-skeleton");
        expect(skeletons.length).toBeGreaterThanOrEqual(4);
    });

    it("正常データで4つのカードが表示される", () => {
        render(<SummaryCards summary={mockSummary} />);
        expect(screen.getByText("本日の売上")).toBeInTheDocument();
        expect(screen.getByText("本日の注文数")).toBeInTheDocument();
        expect(screen.getByText("今月の売上")).toBeInTheDocument();
        expect(screen.getByText("今月の注文数")).toBeInTheDocument();
    });

    it("比較データが正しく表示される", () => {
        render(<SummaryCards summary={mockSummary} />);
        expect(screen.getByText(/11\.1%/)).toBeInTheDocument();
        expect(screen.getByText(/25%.*前日比/)).toBeInTheDocument();
        expect(screen.getAllByText(/7\.1%/).length).toBe(2);
    });
});
