const apiMock = vi.hoisted(() => ({
    cachedGet: vi.fn(),
}));
vi.mock("@/api", async (importOriginal) => {
    const actual = await importOriginal<typeof import("@/api")>();
    return { ...actual, api: apiMock };
});

import { render, screen, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import CustomerInsightsCard from "@/Components/Dashboard/CustomerInsightsCard";

describe("CustomerInsightsCard", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("ローディング中にSpinnerが表示される", () => {
        apiMock.cachedGet.mockReturnValueOnce(new Promise(() => {}));
        render(<CustomerInsightsCard />);
        expect(screen.getByRole("status")).toBeInTheDocument();
    });

    it("APIエラー時にエラーメッセージが表示される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({ data: null, error: "error" });
        render(<CustomerInsightsCard />);
        await waitFor(() => {
            expect(screen.getByText("データの取得に失敗しました")).toBeInTheDocument();
        });
    });

    it("データがnullのとき「データがありません」が表示される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: { data: null },
            error: null,
        });
        render(<CustomerInsightsCard />);
        await waitFor(() => {
            expect(screen.getByText("データがありません")).toBeInTheDocument();
        });
    });

    it("正常データが表示される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: {
                data: {
                    unique_customers: 100,
                    new_customers: 40,
                    repeat_customers: 60,
                    repeat_rate: 60.0,
                },
            },
            error: null,
        });
        render(<CustomerInsightsCard />);
        await waitFor(() => {
            expect(screen.getByText("100")).toBeInTheDocument();
        });
        expect(screen.getByText("40")).toBeInTheDocument();
        expect(screen.getByText("60")).toBeInTheDocument();
        expect(screen.getByText("60.0%")).toBeInTheDocument();
    });

    it("新規%とリピート%が正しく計算される", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: {
                data: {
                    unique_customers: 100,
                    new_customers: 40,
                    repeat_customers: 60,
                    repeat_rate: 60.0,
                },
            },
            error: null,
        });
        render(<CustomerInsightsCard />);
        await waitFor(() => {
            expect(screen.getByText(/新規 40\.0%/)).toBeInTheDocument();
        });
        expect(screen.getByText(/リピート 60\.0%/)).toBeInTheDocument();
    });

    it("新規%とリピート%の小数点以下が正しい", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: {
                data: {
                    unique_customers: 150,
                    new_customers: 50,
                    repeat_customers: 100,
                    repeat_rate: 66.7,
                },
            },
            error: null,
        });
        render(<CustomerInsightsCard />);
        await waitFor(() => {
            // 50/150 * 100 = 33.3333... -> 33.3
            expect(screen.getByText(/新規 33\.3%/)).toBeInTheDocument();
        });
        // 100/150 * 100 = 66.6666... -> 66.7
        expect(screen.getByText(/リピート 66\.7%/)).toBeInTheDocument();
    });

    it("unique_customers === 0 のときパーセントバーが非表示", async () => {
        apiMock.cachedGet.mockResolvedValueOnce({
            data: {
                data: {
                    unique_customers: 0,
                    new_customers: 0,
                    repeat_customers: 0,
                    repeat_rate: 0,
                },
            },
            error: null,
        });
        render(<CustomerInsightsCard />);
        await waitFor(() => {
            expect(screen.getByText("ユニーク顧客数")).toBeInTheDocument();
        });
        // パーセントバーの凡例テキストが表示されない
        expect(screen.queryByText(/新規 \d/)).not.toBeInTheDocument();
        expect(screen.queryByText(/リピート \d/)).not.toBeInTheDocument();
    });
});
