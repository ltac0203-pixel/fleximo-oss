import { render, screen, fireEvent } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import DateRangeSelector, { getDateRangeParams } from "@/Components/Dashboard/DateRangeSelector";

describe("getDateRangeParams", () => {
    it("today: start_date と end_date が同じ日付になる", () => {
        const result = getDateRangeParams("today");
        expect(result.start_date).toBe(result.end_date);
        // YYYY-MM-DD 形式
        expect(result.start_date).toMatch(/^\d{4}-\d{2}-\d{2}$/);
    });

    it("week: start_date と end_date の差が6日になる", () => {
        const result = getDateRangeParams("week");
        const start = new Date(result.start_date);
        const end = new Date(result.end_date);
        const diffDays = (end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24);
        expect(diffDays).toBe(6);
    });

    it("month: start_date が end_date 以前になる", () => {
        const result = getDateRangeParams("month");
        const start = new Date(result.start_date);
        const end = new Date(result.end_date);
        expect(start.getTime()).toBeLessThanOrEqual(end.getTime());
    });
});

describe("DateRangeSelector コンポーネント", () => {
    const mockOnChange = vi.fn();

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("今日・今週・今月の3ボタンが表示される", () => {
        render(<DateRangeSelector selected="today" onChange={mockOnChange} />);
        expect(screen.getByText("今日")).toBeInTheDocument();
        expect(screen.getByText("今週")).toBeInTheDocument();
        expect(screen.getByText("今月")).toBeInTheDocument();
    });

    it("selected='today' のとき今日ボタンにアクティブスタイルが適用される", () => {
        render(<DateRangeSelector selected="today" onChange={mockOnChange} />);
        const todayButton = screen.getByText("今日");
        expect(todayButton).toHaveClass("bg-sky-600");
        expect(todayButton).toHaveClass("text-white");
    });

    it("ボタンクリックで onChange が呼ばれる", () => {
        render(<DateRangeSelector selected="today" onChange={mockOnChange} />);
        fireEvent.click(screen.getByText("今週"));
        expect(mockOnChange).toHaveBeenCalledTimes(1);
    });

    it("各ボタンクリックで正しい value 引数が渡される", () => {
        render(<DateRangeSelector selected="today" onChange={mockOnChange} />);

        fireEvent.click(screen.getByText("今日"));
        expect(mockOnChange).toHaveBeenCalledWith("today");

        fireEvent.click(screen.getByText("今週"));
        expect(mockOnChange).toHaveBeenCalledWith("week");

        fireEvent.click(screen.getByText("今月"));
        expect(mockOnChange).toHaveBeenCalledWith("month");
    });
});
