import { act, renderHook, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { useKdsOrders } from "@/Hooks/useKdsOrders";
import { KdsOrder, KdsOrderStatus } from "@/types";

type KdsApiOrder = KdsOrder | (Omit<KdsOrder, "status"> & { status: "completed" });

const apiMock = vi.hoisted(() => ({
    get: vi.fn(),
    patch: vi.fn(),
}));

vi.mock("@/api", async (importOriginal) => {
    const actual = await importOriginal<typeof import("@/api")>();
    return {
        ...actual,
        api: apiMock,
    };
});

function createDeferred<T>() {
    let resolve!: (value: T) => void;
    const promise = new Promise<T>((res) => {
        resolve = res;
    });

    return { promise, resolve };
}

function toStatusLabel(status: KdsOrderStatus): string {
    switch (status) {
        case "accepted":
            return "受付済み";
        case "in_progress":
            return "調理中";
        case "ready":
            return "準備完了";
    }
}

function createOrder(id: number, status: KdsOrderStatus): KdsOrder {
    return {
        id,
        order_code: `ORD-${id}`,
        status,
        status_label: toStatusLabel(status),
        items: [],
        item_count: 0,
        elapsed_seconds: 120,
        elapsed_display: "2分",
        is_warning: false,
        accepted_at: status === "accepted" ? "2026-02-14T10:00:00Z" : null,
        in_progress_at: status === "in_progress" ? "2026-02-14T10:05:00Z" : null,
        ready_at: status === "ready" ? "2026-02-14T10:10:00Z" : null,
        created_at: "2026-02-14T10:00:00Z",
    };
}

function createOrdersResponse(orders: KdsOrder[], serverTime = "2026-02-14T10:00:00Z") {
    return {
        data: {
            data: orders,
            meta: { server_time: serverTime },
        },
        error: null,
    };
}

function createPatchResponse(order: KdsApiOrder) {
    return {
        data: {
            data: order,
            message: "ok",
        },
        error: null,
    };
}

function createCompletedApiOrder(id: number): KdsApiOrder {
    return {
        ...createOrder(id, "ready"),
        status: "completed",
        status_label: "完了",
    };
}

describe("useKdsOrders", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it("ordersをステータス別に正しく分類する", async () => {
        const initialOrders = [createOrder(1, "accepted"), createOrder(2, "in_progress"), createOrder(3, "ready")];
        apiMock.get.mockResolvedValueOnce(createOrdersResponse(initialOrders));

        const { result } = renderHook(() =>
            useKdsOrders({
                initialOrders,
                initialServerTime: "2026-02-14T09:59:00Z",
            }),
        );

        await waitFor(() => {
            expect(apiMock.get).toHaveBeenCalledTimes(1);
            expect(result.current.acceptedOrders.map((o) => o.id)).toEqual([1]);
            expect(result.current.inProgressOrders.map((o) => o.id)).toEqual([2]);
            expect(result.current.readyOrders.map((o) => o.id)).toEqual([3]);
        });
    });

    it("ordersが空配列のとき分類結果もすべて空配列になる", async () => {
        apiMock.get.mockResolvedValueOnce(createOrdersResponse([]));

        const { result } = renderHook(() =>
            useKdsOrders({
                initialOrders: [],
                initialServerTime: "2026-02-14T09:59:00Z",
            }),
        );

        await waitFor(() => {
            expect(apiMock.get).toHaveBeenCalledTimes(1);
            expect(result.current.acceptedOrders).toEqual([]);
            expect(result.current.inProgressOrders).toEqual([]);
            expect(result.current.readyOrders).toEqual([]);
        });
    });

    it("ポーリング取得時にグローバルローディング抑止オプションを付与する", async () => {
        apiMock.get.mockResolvedValueOnce(createOrdersResponse([]));

        renderHook(() =>
            useKdsOrders({
                initialOrders: [],
                initialServerTime: "2026-02-14T09:59:00Z",
            }),
        );

        await waitFor(() => {
            expect(apiMock.get).toHaveBeenCalledTimes(1);
        });

        const options = apiMock.get.mock.calls[0]?.[1];
        expect(options).toEqual({ suppressGlobalLoading: true });
    });

    it("ステータス更新時にacceptedからin_progressへ即時反映される", async () => {
        const initialOrders = [createOrder(10, "accepted")];
        const updatedOrders = [createOrder(10, "in_progress")];
        const patchDeferred = createDeferred<ReturnType<typeof createPatchResponse>>();

        apiMock.get.mockResolvedValueOnce(createOrdersResponse(initialOrders));
        apiMock.get.mockResolvedValueOnce(createOrdersResponse(updatedOrders, "2026-02-14T10:01:00Z"));
        apiMock.patch.mockReturnValueOnce(patchDeferred.promise);

        const { result } = renderHook(() =>
            useKdsOrders({
                initialOrders,
                initialServerTime: "2026-02-14T09:59:00Z",
            }),
        );

        await waitFor(() => {
            expect(result.current.acceptedOrders.map((o) => o.id)).toEqual([10]);
        });

        let updatePromise = Promise.resolve();
        act(() => {
            updatePromise = result.current.updateOrderStatus(10, "in_progress");
        });

        await waitFor(() => {
            expect(result.current.acceptedOrders).toHaveLength(0);
            expect(result.current.inProgressOrders.map((o) => o.id)).toEqual([10]);
        });

        await act(async () => {
            patchDeferred.resolve(createPatchResponse(updatedOrders[0]));
            await updatePromise;
        });

        expect(apiMock.patch).toHaveBeenCalledWith("/api/tenant/kds/orders/10/status", {
            status: "in_progress",
        });
        expect(result.current.readyOrders).toHaveLength(0);
    });

    it("ステータス更新直後の古いGET結果で楽観更新を巻き戻さない", async () => {
        const initialOrders = [createOrder(30, "accepted")];
        const staleOrders = [createOrder(30, "accepted")];

        apiMock.get.mockResolvedValueOnce(createOrdersResponse(initialOrders, "2026-02-14T10:00:00Z"));
        apiMock.get.mockResolvedValueOnce(createOrdersResponse(staleOrders, "2026-02-14T10:01:00Z"));
        apiMock.patch.mockResolvedValueOnce(createPatchResponse(createOrder(30, "in_progress")));

        const { result } = renderHook(() =>
            useKdsOrders({
                initialOrders,
                initialServerTime: "2026-02-14T09:59:00Z",
            }),
        );

        await waitFor(() => {
            expect(result.current.acceptedOrders.map((o) => o.id)).toEqual([30]);
        });

        await act(async () => {
            await result.current.updateOrderStatus(30, "in_progress");
        });

        await waitFor(() => {
            expect(apiMock.get).toHaveBeenCalledTimes(2);
            expect(result.current.acceptedOrders).toHaveLength(0);
            expect(result.current.inProgressOrders.map((o) => o.id)).toEqual([30]);
        });
    });

    it("completed更新後に古いGETが来ても注文カードを再表示しない", async () => {
        const initialOrders = [createOrder(40, "ready")];

        apiMock.get.mockResolvedValueOnce(createOrdersResponse(initialOrders, "2026-02-14T10:00:00Z"));
        apiMock.get.mockResolvedValueOnce(createOrdersResponse(initialOrders, "2026-02-14T10:01:00Z"));
        apiMock.patch.mockResolvedValueOnce(createPatchResponse(createCompletedApiOrder(40)));

        const { result } = renderHook(() =>
            useKdsOrders({
                initialOrders,
                initialServerTime: "2026-02-14T09:59:00Z",
            }),
        );

        await waitFor(() => {
            expect(result.current.readyOrders.map((o) => o.id)).toEqual([40]);
        });

        await act(async () => {
            await result.current.updateOrderStatus(40, "completed");
        });

        await waitFor(() => {
            expect(apiMock.get).toHaveBeenCalledTimes(2);
            expect(result.current.readyOrders).toHaveLength(0);
            expect(result.current.orders.find((o) => o.id === 40)).toBeUndefined();
        });
    });

    it("古いGETを無視した場合はlastServerTimeを進めず同じupdated_sinceで再取得する", async () => {
        const initialOrders = [createOrder(50, "accepted")];
        const syncedOrder = createOrder(50, "in_progress");

        apiMock.get.mockResolvedValueOnce(createOrdersResponse(initialOrders, "2026-02-14T10:00:00Z"));
        apiMock.get.mockResolvedValueOnce(createOrdersResponse(initialOrders, "2026-02-14T10:01:00Z"));
        apiMock.patch.mockResolvedValueOnce(createPatchResponse(syncedOrder));

        const { result } = renderHook(() =>
            useKdsOrders({
                initialOrders,
                initialServerTime: "2026-02-14T09:59:00Z",
            }),
        );

        await waitFor(() => {
            expect(apiMock.get).toHaveBeenCalledTimes(1);
            expect(result.current.lastServerTime).toBe("2026-02-14T10:00:00Z");
        });

        await act(async () => {
            await result.current.updateOrderStatus(50, "in_progress");
        });

        await waitFor(() => {
            expect(apiMock.get).toHaveBeenCalledTimes(2);
        });

        expect(result.current.lastServerTime).toBe("2026-02-14T10:00:00Z");

        apiMock.get.mockResolvedValueOnce(createOrdersResponse([syncedOrder], "2026-02-14T10:02:00Z"));

        await act(async () => {
            await result.current.refresh();
        });

        const refreshRequestUrl = apiMock.get.mock.calls[2][0] as string;
        expect(refreshRequestUrl).toContain("updated_since=2026-02-14T10%3A00%3A00Z");
        expect(result.current.lastServerTime).toBe("2026-02-14T10:02:00Z");
    });

    it("差分0件の場合はsetOrdersが呼ばれずordersの参照が変わらない", async () => {
        vi.useFakeTimers();
        const initialOrders = [createOrder(60, "accepted")];

        // 初回フェッチ: 全件取得
        apiMock.get.mockResolvedValueOnce(createOrdersResponse(initialOrders, "2026-02-14T10:00:00Z"));
        // 2回目: 差分0件を返す
        apiMock.get.mockResolvedValueOnce(createOrdersResponse([], "2026-02-14T10:01:00Z"));

        const { result } = renderHook(() =>
            useKdsOrders({
                initialOrders,
                initialServerTime: "2026-02-14T09:59:00Z",
                pollingInterval: 500,
            }),
        );

        // 初回フェッチを完了させる
        await act(async () => {
            await vi.advanceTimersByTimeAsync(0);
        });

        expect(apiMock.get).toHaveBeenCalledTimes(1);

        // 初回フェッチ後のordersの参照を取得
        const ordersAfterFirstFetch = result.current.orders;

        // タイマーを進めて2回目のポーリングをトリガー
        await act(async () => {
            await vi.advanceTimersByTimeAsync(600);
        });

        expect(apiMock.get).toHaveBeenCalledTimes(2);

        // 差分0件だったので、ordersの参照が変わっていないことを確認
        expect(result.current.orders).toBe(ordersAfterFirstFetch);
    });

    it("サーバー取得結果に応じて分類が更新される", async () => {
        const initialOrders = [createOrder(20, "accepted")];
        const fetchedOrders = [createOrder(20, "ready")];
        apiMock.get.mockResolvedValueOnce(createOrdersResponse(fetchedOrders));

        const { result } = renderHook(() =>
            useKdsOrders({
                initialOrders,
                initialServerTime: "2026-02-14T09:59:00Z",
            }),
        );

        await waitFor(() => {
            expect(result.current.acceptedOrders).toHaveLength(0);
            expect(result.current.readyOrders.map((o) => o.id)).toEqual([20]);
        });

        expect(apiMock.get).toHaveBeenCalled();
    });

    it("アンマウント後は進行中フェッチ完了時に次回ポーリングを再登録しない", async () => {
        vi.useFakeTimers();
        const deferredFetch = createDeferred<ReturnType<typeof createOrdersResponse>>();

        apiMock.get.mockReturnValueOnce(deferredFetch.promise);

        const { unmount } = renderHook(() =>
            useKdsOrders({
                initialOrders: [],
                initialServerTime: "2026-02-14T09:59:00Z",
                pollingInterval: 50,
            }),
        );

        await act(async () => {
            await Promise.resolve();
        });

        expect(apiMock.get).toHaveBeenCalledTimes(1);

        unmount();

        await act(async () => {
            deferredFetch.resolve(createOrdersResponse([]));
            await Promise.resolve();
        });

        await act(async () => {
            vi.advanceTimersByTime(500);
        });

        expect(apiMock.get).toHaveBeenCalledTimes(1);
        expect(vi.getTimerCount()).toBe(0);
    });

    it("アンマウント後にポーリング失敗通知を発火しない", async () => {
        const deferredFetch = createDeferred<{ data: null; error: string }>();
        const onPollingError = vi.fn();

        apiMock.get.mockReturnValueOnce(deferredFetch.promise);

        const { unmount } = renderHook(() =>
            useKdsOrders({
                initialOrders: [],
                initialServerTime: "2026-02-14T09:59:00Z",
                onPollingError,
            }),
        );

        await act(async () => {
            await Promise.resolve();
        });

        expect(apiMock.get).toHaveBeenCalledTimes(1);

        unmount();

        await act(async () => {
            deferredFetch.resolve({ data: null, error: "注文の取得に失敗しました" });
            await Promise.resolve();
        });

        expect(onPollingError).not.toHaveBeenCalled();
    });
});
