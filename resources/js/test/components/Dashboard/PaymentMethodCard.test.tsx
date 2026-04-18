const apiMock = vi.hoisted(() => ({
    cachedGet: vi.fn(),
}));
vi.mock("@/api", () => ({ api: apiMock }));
vi.mock("recharts", () => import("../../helpers/chartMock"));

import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { getDateRangeParams } from "@/Components/Dashboard/DateRangeSelector";
import PaymentMethodCard from "@/Components/Dashboard/PaymentMethodCard";
import { setChartMockRenderError } from "../../helpers/chartMock";

const normalData = {
    methods: [
        { method: "card", label: "クレジットカード", count: 80, amount: 160000 },
        { method: "paypay", label: "PayPay", count: 20, amount: 40000 },
    ],
    total_count: 100,
    total_amount: 200000,
};

const allZeroData = {
    methods: [
        { method: "card", label: "クレジットカード", count: 0, amount: 0 },
        { method: "paypay", label: "PayPay", count: 0, amount: 0 },
    ],
    total_count: 0,
    total_amount: 0,
};

function getRequestUrl(url: string): URL {
    return new URL(url, "http://localhost");
}

describe("PaymentMethodCard", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("ローディング中にSpinnerが表示される", () => {
        apiMock.cachedGet.mockReturnValueOnce(new Promise(() => {}));
        render(<PaymentMethodCard />);
        expect(screen.getByRole("status")).toBeInTheDocument();
    });

    it("APIエラー時にエラーメッセージが表示される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({ data: null, error: "error" });
        render(<PaymentMethodCard />);
        await waitFor(() => {
            expect(screen.getByText("データの取得に失敗しました")).toBeInTheDocument();
        });
    });

    it("API例外時にエラーメッセージが表示される", async () => {
        apiMock.cachedGet.mockRejectedValueOnce(new Error("network down"));
        render(<PaymentMethodCard />);
        await waitFor(() => {
            expect(screen.getByText("データの取得に失敗しました")).toBeInTheDocument();
        });
    });

    it("全てのmethodsのcount=0のとき「データがありません」が表示される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: allZeroData },
            error: null,
        });
        render(<PaymentMethodCard />);
        await waitFor(() => {
            expect(screen.getByText("データがありません")).toBeInTheDocument();
        });
    });

    it("正常データで決済方法名、件数、金額が表示される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        render(<PaymentMethodCard />);
        await waitFor(() => {
            expect(screen.getByText("クレジットカード")).toBeInTheDocument();
        });
        expect(screen.getByText("PayPay")).toBeInTheDocument();
        expect(screen.getByText("80件")).toBeInTheDocument();
        expect(screen.getByText("20件")).toBeInTheDocument();
        expect(screen.getByText("¥160,000")).toBeInTheDocument();
        expect(screen.getByText("¥40,000")).toBeInTheDocument();
    });

    it("recharts描画エラー時でもフォールバックと凡例情報が表示される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        setChartMockRenderError("PieChart");

        render(<PaymentMethodCard />);

        await waitFor(() => {
            expect(screen.getByText("チャートの表示に失敗しました")).toBeInTheDocument();
        });
        expect(screen.getByText("クレジットカード")).toBeInTheDocument();
    });

    it("チャートにaria-labelがあり、キーボード操作で詳細を確認できる", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        render(<PaymentMethodCard />);

        const chart = await screen.findByRole("group", { name: "決済方法別チャート" });
        fireEvent.focus(chart);
        await waitFor(() => {
            const keyboardTooltip = screen.getByRole("status");
            expect(keyboardTooltip).toHaveTextContent("クレジットカード");
            expect(keyboardTooltip).toHaveTextContent("[斜線]");
        });

        fireEvent.keyDown(chart, { key: "ArrowRight" });
        await waitFor(() => {
            const keyboardTooltip = screen.getByRole("status");
            expect(keyboardTooltip).toHaveTextContent("PayPay");
            expect(keyboardTooltip).toHaveTextContent("[ドット]");
        });
    });

    it("金額割合が正しく計算される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        render(<PaymentMethodCard />);
        await waitFor(() => {
            expect(screen.getByText("クレジットカード")).toBeInTheDocument();
        });
        // card比率: 160000 / 200000 * 100 = 80.0%
        expect(screen.getByText(/80\.0/)).toBeInTheDocument();
        // paypay比率: 40000 / 200000 * 100 = 20.0%
        expect(screen.getByText(/20\.0/)).toBeInTheDocument();
    });

    it("count=0のメソッドは非表示", async () => {
        const mixedData = {
            methods: [
                { method: "card", label: "クレジットカード", count: 50, amount: 100000 },
                { method: "paypay", label: "PayPay", count: 0, amount: 0 },
            ],
            total_count: 50,
            total_amount: 100000,
        };
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: mixedData },
            error: null,
        });
        render(<PaymentMethodCard />);
        await waitFor(() => {
            expect(screen.getByText("クレジットカード")).toBeInTheDocument();
        });
        expect(screen.queryByText("PayPay")).not.toBeInTheDocument();
    });

    it("DateRange切替で正しい日付パラメータを付与して再取得される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        render(<PaymentMethodCard />);

        await waitFor(() => {
            expect(screen.getByText("クレジットカード")).toBeInTheDocument();
        });
        expect(apiMock.cachedGet).toHaveBeenCalledTimes(1);

        const firstCall = getRequestUrl(apiMock.cachedGet.mock.calls[0][0] as string);
        const monthParams = getDateRangeParams("month");
        expect(firstCall.pathname).toBe("/api/tenant/dashboard/payment-methods");
        expect(firstCall.searchParams.get("start_date")).toBe(monthParams.start_date);
        expect(firstCall.searchParams.get("end_date")).toBe(monthParams.end_date);

        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        fireEvent.click(screen.getByRole("button", { name: "今週" }));
        await waitFor(() => {
            expect(apiMock.cachedGet).toHaveBeenCalledTimes(2);
        });

        const secondCall = getRequestUrl(apiMock.cachedGet.mock.calls[1][0] as string);
        const weekParams = getDateRangeParams("week");
        expect(secondCall.searchParams.get("start_date")).toBe(weekParams.start_date);
        expect(secondCall.searchParams.get("end_date")).toBe(weekParams.end_date);

        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        fireEvent.click(screen.getByRole("button", { name: "今日" }));
        await waitFor(() => {
            expect(apiMock.cachedGet).toHaveBeenCalledTimes(3);
        });

        const thirdCall = getRequestUrl(apiMock.cachedGet.mock.calls[2][0] as string);
        const todayParams = getDateRangeParams("today");
        expect(thirdCall.searchParams.get("start_date")).toBe(todayParams.start_date);
        expect(thirdCall.searchParams.get("end_date")).toBe(todayParams.end_date);
    });

    it("DateRange切替中はローディング表示になる", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        render(<PaymentMethodCard />);

        await waitFor(() => {
            expect(screen.getByText("クレジットカード")).toBeInTheDocument();
        });

        apiMock.cachedGet.mockReturnValueOnce(new Promise(() => {}));
        fireEvent.click(screen.getByRole("button", { name: "今週" }));

        await waitFor(() => {
            expect(screen.getByRole("status", { name: "読み込み中" })).toBeInTheDocument();
        });
    });

});
