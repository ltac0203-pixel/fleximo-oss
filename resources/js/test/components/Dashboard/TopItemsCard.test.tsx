const apiMock = vi.hoisted(() => ({
    cachedGet: vi.fn(),
}));
const loggerMock = vi.hoisted(() => ({
    error: vi.fn(),
}));
vi.mock("@/api", async (importOriginal) => {
    const actual = await importOriginal<typeof import("@/api")>();
    return { ...actual, api: apiMock };
});
vi.mock("@/Utils/logger", () => ({ logger: loggerMock }));

import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { beforeEach, describe, expect, it, vi } from "vitest";
import TopItemsCard from "@/Components/Dashboard/TopItemsCard";

const normalData = [
    { rank: 1, menu_item_id: 1, name: "カレー", quantity: 100, revenue: 100000 },
    { rank: 2, menu_item_id: 2, name: "ラーメン", quantity: 80, revenue: 80000 },
];

describe("TopItemsCard", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("ローディング中にSpinnerが表示される", () => {
        apiMock.cachedGet.mockReturnValueOnce(new Promise(() => {}));
        render(<TopItemsCard />);
        expect(screen.getByRole("status")).toBeInTheDocument();
    });

    it("APIエラー時にエラーメッセージが表示される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({ data: null, error: "error" });
        render(<TopItemsCard />);
        await waitFor(() => {
            expect(screen.getByText("データの取得に失敗しました")).toBeInTheDocument();
            expect(loggerMock.error).toHaveBeenCalledWith("Dashboard top items fetch failed", "error", {
                period: "month",
            });
        });
    });

    it("データが空配列のとき「データがありません」が表示される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: [] },
            error: null,
        });
        render(<TopItemsCard />);
        await waitFor(() => {
            expect(screen.getByText("データがありません")).toBeInTheDocument();
        });
    });

    it("正常データでランキング番号、商品名、数量、売上が表示される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        render(<TopItemsCard />);
        await waitFor(() => {
            expect(screen.getByText("カレー")).toBeInTheDocument();
        });
        expect(screen.getByText("1")).toBeInTheDocument();
        expect(screen.getByText("2")).toBeInTheDocument();
        expect(screen.getByText("ラーメン")).toBeInTheDocument();
        expect(screen.getByText("100個")).toBeInTheDocument();
        expect(screen.getByText("80個")).toBeInTheDocument();
        expect(screen.getByText("¥100,000")).toBeInTheDocument();
        expect(screen.getByText("¥80,000")).toBeInTheDocument();
    });

    it("期間切替ボタン(週間/月間/年間)が表示される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        render(<TopItemsCard />);
        await waitFor(() => {
            expect(screen.getByText("カレー")).toBeInTheDocument();
        });
        expect(screen.getByText("週間")).toBeInTheDocument();
        expect(screen.getByText("月間")).toBeInTheDocument();
        expect(screen.getByText("年間")).toBeInTheDocument();
    });

    it("期間切替でAPI再取得される", async () => {
        const user = userEvent.setup();
        // 初回取得（month）
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        render(<TopItemsCard />);
        await waitFor(() => {
            expect(screen.getByText("カレー")).toBeInTheDocument();
        });
        expect(apiMock.cachedGet).toHaveBeenCalledTimes(1);

        // 「週間」ボタンクリック
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: normalData },
            error: null,
        });
        await user.click(screen.getByText("週間"));
        await waitFor(() => {
            expect(apiMock.cachedGet).toHaveBeenCalledTimes(2);
        });
    });
});
