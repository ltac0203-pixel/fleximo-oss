const apiMock = vi.hoisted(() => ({
    cachedGet: vi.fn(),
}));
const loggerMock = vi.hoisted(() => ({
    error: vi.fn(),
    exception: vi.fn(),
}));
vi.mock("@/api", () => ({ api: apiMock }));
vi.mock("@/Utils/logger", () => ({ logger: loggerMock }));
vi.mock("recharts", () => import("../../helpers/chartMock"));

import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { beforeEach, describe, expect, it, vi } from "vitest";
import SalesChart from "@/Components/Dashboard/SalesChart";
import { setChartMockRenderError } from "../../helpers/chartMock";

const initialData = [
    { date: "2025-06-15", sales: 50000, orders: 25 },
    { date: "2025-06-14", sales: 45000, orders: 20 },
];

function getRequestUrl(url: string): URL {
    return new URL(url, "http://localhost");
}

describe("SalesChart", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("initialDataが空配列のとき「データがありません」が表示される", () => {
        render(<SalesChart initialData={[]} />);
        expect(screen.getByText("データがありません")).toBeInTheDocument();
    });

    it("initialDataがあるときチャートにデータが渡される", () => {
        render(<SalesChart initialData={initialData} />);
        expect(screen.getByTestId("mock-ResponsiveContainer")).toBeInTheDocument();
        expect(screen.getByTestId("mock-LineChart")).toBeInTheDocument();
    });

    it("recharts描画エラー時にチャート領域のフォールバックが表示される", () => {
        setChartMockRenderError("LineChart");
        render(<SalesChart initialData={initialData} />);

        expect(screen.getByText("売上推移")).toBeInTheDocument();
        expect(screen.getByText("チャートの表示に失敗しました")).toBeInTheDocument();
    });

    it("チャートにaria-labelがあり、キーボード操作で詳細を確認できる", async () => {
        render(<SalesChart initialData={initialData} />);

        const chart = screen.getByRole("group", { name: "売上推移チャート" });
        fireEvent.focus(chart);
        await waitFor(() => {
            const keyboardTooltip = screen.getByRole("status");
            expect(keyboardTooltip).toHaveTextContent("6/15");
            expect(keyboardTooltip).toHaveTextContent("[実線] 売上: ¥50,000");
        });

        fireEvent.keyDown(chart, { key: "ArrowRight" });
        await waitFor(() => {
            const keyboardTooltip = screen.getByRole("status");
            expect(keyboardTooltip).toHaveTextContent("6/14");
            expect(keyboardTooltip).toHaveTextContent("[破線] 注文数: 20件");
        });
    });

    it("日付フォーマット: dailyのときM/D形式でチャートに渡される", () => {
        render(<SalesChart initialData={initialData} />);
        const lineChart = screen.getByTestId("mock-LineChart");
        const chartData = lineChart.getAttribute("data-chart-data");
        expect(chartData).toBeTruthy();
        const parsed = JSON.parse(chartData!);
        // 2025-06-15 -> 6/15
        expect(parsed[0].dateLabel).toBe("6/15");
        expect(parsed[1].dateLabel).toBe("6/14");
    });

    it("期間セレクタ(日次/週次/月次)が表示される", () => {
        render(<SalesChart initialData={initialData} />);
        expect(screen.getByText("日次")).toBeInTheDocument();
        expect(screen.getByText("週次")).toBeInTheDocument();
        expect(screen.getByText("月次")).toBeInTheDocument();
    });

    it("期間切替でAPI再取得される", async () => {
        const user = userEvent.setup();
        render(<SalesChart initialData={initialData} />);

        // 初回表示ではinitialDataを使うのでAPIは呼ばれない
        expect(apiMock.cachedGet).not.toHaveBeenCalled();

        // 「週次」ボタンクリック
        apiMock.cachedGet.mockResolvedValueOnce({
            data: {
                data: [{ date: "2025-06-09", sales: 300000, orders: 150 }],
            },
            error: null,
        });
        await user.click(screen.getByText("週次"));
        await waitFor(() => {
            expect(apiMock.cachedGet).toHaveBeenCalledTimes(1);
        });

        const requestUrl = getRequestUrl(apiMock.cachedGet.mock.calls[0][0] as string);
        expect(requestUrl.pathname).toBe("/api/tenant/dashboard/sales");
        expect(requestUrl.searchParams.get("period")).toBe("weekly");
        expect(requestUrl.searchParams.get("start_date")).toBeTruthy();
        expect(requestUrl.searchParams.get("end_date")).toBeTruthy();
    });

    it("期間切替中はローディング表示になる", async () => {
        const user = userEvent.setup();
        render(<SalesChart initialData={initialData} />);

        apiMock.cachedGet.mockReturnValueOnce(new Promise(() => {}));
        await user.click(screen.getByText("週次"));

        await waitFor(() => {
            expect(screen.getByRole("status", { name: "読み込み中" })).toBeInTheDocument();
        });
    });

    it("期間切替後にデータが空配列なら「データがありません」が表示される", async () => {
        const user = userEvent.setup();
        render(<SalesChart initialData={initialData} />);

        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: [] },
            error: null,
        });
        await user.click(screen.getByText("週次"));

        await waitFor(() => {
            expect(screen.getByText("データがありません")).toBeInTheDocument();
        });
    });

    it("APIエラー時に「データの取得に失敗しました」が表示される", async () => {
        const user = userEvent.setup();
        render(<SalesChart initialData={initialData} />);

        apiMock.cachedGet.mockResolvedValueOnce({ data: null, error: "error" });
        await user.click(screen.getByText("月次"));
        await waitFor(() => {
            expect(screen.getByText("データの取得に失敗しました")).toBeInTheDocument();
            expect(loggerMock.error).toHaveBeenCalledWith("Dashboard sales fetch failed", "error", {
                period: "monthly",
            });
        });
    });

    it("月次切替時にM月形式でデータが渡される", async () => {
        const user = userEvent.setup();
        render(<SalesChart initialData={initialData} />);

        apiMock.cachedGet.mockResolvedValueOnce({
            data: {
                data: [
                    { date: "2025-06", sales: 300000, orders: 150 },
                    { date: "2025-05", sales: 250000, orders: 130 },
                ],
            },
            error: null,
        });
        await user.click(screen.getByText("月次"));
        await waitFor(() => {
            const lineChart = screen.getByTestId("mock-LineChart");
            const chartData = lineChart.getAttribute("data-chart-data");
            expect(chartData).toBeTruthy();
            const parsed = JSON.parse(chartData!);
            expect(parsed[0].dateLabel).toBe("06月");
            expect(parsed[1].dateLabel).toBe("05月");
        });
    });

});
