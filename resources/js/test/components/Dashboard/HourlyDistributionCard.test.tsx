const apiMock = vi.hoisted(() => ({
    cachedGet: vi.fn(),
}));
const loggerMock = vi.hoisted(() => ({
    error: vi.fn(),
    exception: vi.fn(),
}));
vi.mock("@/api", async (importOriginal) => {
    const actual = await importOriginal<typeof import("@/api")>();
    return { ...actual, api: apiMock };
});
vi.mock("@/Utils/logger", () => ({ logger: loggerMock }));
vi.mock("recharts", () => import("../../helpers/chartMock"));

import { render, screen, waitFor, fireEvent } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import HourlyDistributionCard from "@/Components/Dashboard/HourlyDistributionCard";
import { setChartMockRenderError } from "../../helpers/chartMock";

const normalData = [
    { hour: 11, orders: 10, sales: 20000 },
    { hour: 12, orders: 25, sales: 50000 },
    { hour: 13, orders: 15, sales: 30000 },
];

function getSearchParamsFromCall(url: string): URLSearchParams {
    return new URL(url, "http://localhost").searchParams;
}

describe("HourlyDistributionCard", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("ローディング中にSpinnerが表示される", () => {
        apiMock.cachedGet.mockReturnValueOnce(new Promise(() => {}));
        render(<HourlyDistributionCard />);
        expect(screen.getByRole("status")).toBeInTheDocument();
    });

    it("APIエラー時にエラーメッセージが表示される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({ data: null, error: "error" });
        render(<HourlyDistributionCard />);
        await waitFor(() => {
            expect(screen.getByText("データの取得に失敗しました")).toBeInTheDocument();
            expect(loggerMock.error).toHaveBeenCalledWith(
                "Dashboard hourly distribution fetch failed",
                "error",
                expect.objectContaining({ date: expect.any(String) }),
            );
        });
    });

    it("データが空配列のとき「データがありません」が表示される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: [] },
            error: null,
        });
        render(<HourlyDistributionCard />);
        await waitFor(() => {
            expect(screen.getByText("データがありません")).toBeInTheDocument();
        });
    });

    it("正常データでチャートにデータが渡される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        render(<HourlyDistributionCard />);
        await waitFor(() => {
            expect(screen.getByTestId("mock-ResponsiveContainer")).toBeInTheDocument();
        });
        expect(screen.getByTestId("mock-BarChart")).toBeInTheDocument();
    });

    it("recharts描画エラー時にチャート領域のフォールバックが表示される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        setChartMockRenderError("BarChart");

        render(<HourlyDistributionCard />);

        await waitFor(() => {
            expect(screen.getByText("チャートの表示に失敗しました")).toBeInTheDocument();
        });
        expect(screen.getByText("時間帯別注文")).toBeInTheDocument();
    });

    it("チャートにaria-labelがあり、キーボード操作で詳細を確認できる", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        render(<HourlyDistributionCard />);

        const chart = await screen.findByRole("group", { name: "時間帯別注文チャート" });
        fireEvent.focus(chart);
        await waitFor(() => {
            const keyboardTooltip = screen.getByRole("status");
            expect(keyboardTooltip).toHaveTextContent("11時台");
            expect(keyboardTooltip).toHaveTextContent("[注文数] 10件");
        });

        fireEvent.keyDown(chart, { key: "ArrowRight" });
        await waitFor(() => {
            const keyboardTooltip = screen.getByRole("status");
            expect(keyboardTooltip).toHaveTextContent("12時台");
            expect(keyboardTooltip).toHaveTextContent("[売上] ¥50,000");
        });
    });

    it("日付変更でAPI再取得される", async () => {
        const today = new Date().toISOString().split("T")[0];
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        render(<HourlyDistributionCard />);
        await waitFor(() => {
            expect(screen.getByTestId("mock-BarChart")).toBeInTheDocument();
        });
        expect(apiMock.cachedGet).toHaveBeenCalledTimes(1);
        expect(apiMock.cachedGet).toHaveBeenNthCalledWith(1, `/api/tenant/dashboard/hourly?date=${today}`);

        // 日付変更
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        const dateInput = screen.getByDisplayValue(new Date().toISOString().split("T")[0]);
        fireEvent.change(dateInput, { target: { value: "2025-06-01" } });
        await waitFor(() => {
            expect(apiMock.cachedGet).toHaveBeenCalledTimes(2);
        });
        expect(apiMock.cachedGet).toHaveBeenLastCalledWith("/api/tenant/dashboard/hourly?date=2025-06-01");

        const params = getSearchParamsFromCall(apiMock.cachedGet.mock.calls[1][0] as string);
        expect(params.get("date")).toBe("2025-06-01");
    });

    it("日付変更中はローディング表示になる", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        render(<HourlyDistributionCard />);

        await waitFor(() => {
            expect(screen.getByTestId("mock-BarChart")).toBeInTheDocument();
        });

        apiMock.cachedGet.mockReturnValueOnce(new Promise(() => {}));
        const dateInput = screen.getByDisplayValue(new Date().toISOString().split("T")[0]);
        fireEvent.change(dateInput, { target: { value: "2025-06-01" } });

        await waitFor(() => {
            expect(screen.getByRole("status", { name: "読み込み中" })).toBeInTheDocument();
        });
    });

});
