import { act, renderHook, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { useOrderStatusPolling } from "@/Hooks/useOrderStatusPolling";
import { OrderStatusValue } from "@/types";

const apiMock = vi.hoisted(() => ({
    get: vi.fn(),
}));

vi.mock("@/api", async (importOriginal) => {
    const actual = await importOriginal<typeof import("@/api")>();
    return {
        ...actual,
        api: apiMock,
    };
});

function createStatusResponse(
    id: number,
    status: OrderStatusValue,
    options: { is_terminal?: boolean; ready_at?: string | null } = {},
) {
    const labels: Record<string, string> = {
        pending: "注文確認中",
        paid: "決済完了",
        accepted: "受付済み",
        in_progress: "調理中",
        ready: "準備完了",
        completed: "完了",
        cancelled: "キャンセル",
    };

    return {
        data: {
            data: {
                id,
                status,
                status_label: labels[status] ?? status,
                is_terminal: options.is_terminal ?? false,
                ready_at: options.ready_at ?? null,
                updated_at: "2026-03-01T10:00:00Z",
            },
        },
        error: null,
    };
}

describe("useOrderStatusPolling", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it("ポーリング取得時にsuppressGlobalLoading: trueを付与する", async () => {
        apiMock.get.mockResolvedValueOnce(
            createStatusResponse(1, "accepted"),
        );

        renderHook(() =>
            useOrderStatusPolling({
                orderId: 1,
                initialStatus: "accepted",
                initialStatusLabel: "受付済み",
                enabled: true,
            }),
        );

        await waitFor(() => {
            expect(apiMock.get).toHaveBeenCalledTimes(1);
        });

        const options = apiMock.get.mock.calls[0]?.[1];
        expect(options).toEqual({ suppressGlobalLoading: true });
    });

    it("サーバーからis_terminal=trueを受信後はisTerminalがtrueになる", async () => {
        apiMock.get.mockResolvedValueOnce(
            createStatusResponse(1, "completed" as OrderStatusValue, { is_terminal: true }),
        );

        const { result } = renderHook(() =>
            useOrderStatusPolling({
                orderId: 1,
                initialStatus: "accepted",
                initialStatusLabel: "受付済み",
                enabled: true,
            }),
        );

        await waitFor(() => {
            expect(apiMock.get).toHaveBeenCalledTimes(1);
            expect(result.current.isTerminal).toBe(true);
            expect(result.current.status).toBe("completed");
            expect(result.current.statusLabel).toBe("完了");
        });
    });

    it("レスポンスのstatusLabelとreadyAtを一貫して更新する", async () => {
        apiMock.get.mockResolvedValueOnce(
            createStatusResponse(1, "ready", { is_terminal: true, ready_at: "2026-03-01T10:05:00Z" }),
        );

        const { result } = renderHook(() =>
            useOrderStatusPolling({
                orderId: 1,
                initialStatus: "in_progress",
                initialStatusLabel: "調理中",
                enabled: true,
            }),
        );

        await waitFor(() => {
            expect(apiMock.get).toHaveBeenCalledTimes(1);
            expect(result.current.status).toBe("ready");
            expect(result.current.statusLabel).toBe("準備完了");
            expect(result.current.readyAt).toBe("2026-03-01T10:05:00Z");
            expect(result.current.isReady).toBe(true);
            expect(result.current.isTerminal).toBe(true);
        });
    });

    it("cancelledステータスの場合はポーリングを開始しない", async () => {
        renderHook(() =>
            useOrderStatusPolling({
                orderId: 1,
                initialStatus: "cancelled" as OrderStatusValue,
                initialStatusLabel: "キャンセル",
                enabled: true,
            }),
        );

        await act(async () => {
            await new Promise((r) => setTimeout(r, 50));
        });

        expect(apiMock.get).not.toHaveBeenCalled();
    });

    it("onReadyコールバックはready到達時に1回だけ発火する", async () => {
        const onReady = vi.fn();

        // 1回目: readyかつis_terminal=trueを返す（ポーリングを止める）
        apiMock.get.mockResolvedValueOnce(
            createStatusResponse(1, "ready", { is_terminal: true, ready_at: "2026-03-01T10:05:00Z" }),
        );

        renderHook(() =>
            useOrderStatusPolling({
                orderId: 1,
                initialStatus: "in_progress",
                initialStatusLabel: "調理中",
                enabled: true,
                onReady,
            }),
        );

        await waitFor(() => {
            expect(apiMock.get).toHaveBeenCalledTimes(1);
        });

        expect(onReady).toHaveBeenCalledTimes(1);
    });

    it("ステータス変更時にonStatusChangeコールバックが発火する", async () => {
        const onStatusChange = vi.fn();

        apiMock.get.mockResolvedValueOnce(
            createStatusResponse(1, "in_progress"),
        );

        const { result } = renderHook(() =>
            useOrderStatusPolling({
                orderId: 1,
                initialStatus: "accepted",
                initialStatusLabel: "受付済み",
                enabled: true,
                onStatusChange,
            }),
        );

        await waitFor(() => {
            expect(apiMock.get).toHaveBeenCalledTimes(1);
            expect(result.current.status).toBe("in_progress");
        });

        expect(onStatusChange).toHaveBeenCalledWith("in_progress", "accepted");
    });

    it("enabled=falseの場合はポーリングしない", async () => {
        renderHook(() =>
            useOrderStatusPolling({
                orderId: 1,
                initialStatus: "accepted",
                initialStatusLabel: "受付済み",
                enabled: false,
            }),
        );

        await act(async () => {
            await new Promise((r) => setTimeout(r, 50));
        });

        expect(apiMock.get).not.toHaveBeenCalled();
    });
});
