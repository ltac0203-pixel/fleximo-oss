import { useCallback, useRef, useState } from "react";
import { api } from "@/api";
import { ENDPOINTS } from "@/api/endpoints";
import { OrderStatusValue } from "@/types";
import { useLatest } from "./useLatest";
import { usePollingTimer, type PollResult } from "./usePollingTimer";

interface OrderStatusResponse {
    data: {
        id: number;
        status: OrderStatusValue;
        status_label: string;
        is_terminal: boolean;
        ready_at: string | null;
        updated_at: string;
    };
}

interface UseOrderStatusPollingOptions {
    orderId: number;
    initialStatus: OrderStatusValue;
    initialStatusLabel: string;
    enabled?: boolean;
    onStatusChange?: (newStatus: OrderStatusValue, prevStatus: OrderStatusValue) => void;
    onReady?: () => void;
}

interface UseOrderStatusPollingReturn {
    status: OrderStatusValue;
    statusLabel: string;
    isReady: boolean;
    isTerminal: boolean;
    readyAt: string | null;
}

interface OrderPollingState {
    status: OrderStatusValue;
    statusLabel: string;
    isTerminal: boolean;
    readyAt: string | null;
}

const DEFAULT_POLLING_INTERVAL = 10000;

// 終端状態またはキャンセルされた注文はポーリング不要
function shouldStopPolling(status: OrderStatusValue, isTerminal: boolean): boolean {
    return isTerminal || status === "cancelled";
}

export function useOrderStatusPolling({
    orderId,
    initialStatus,
    initialStatusLabel,
    enabled = true,
    onStatusChange,
    onReady,
}: UseOrderStatusPollingOptions): UseOrderStatusPollingReturn {
    const intervalMs = Number(
        import.meta.env.VITE_ORDER_STATUS_POLLING_INTERVAL_MS ?? DEFAULT_POLLING_INTERVAL,
    );

    const initialOrderState: OrderPollingState = {
        status: initialStatus,
        statusLabel: initialStatusLabel,
        isTerminal: false,
        readyAt: null,
    };

    const [orderState, setOrderState] = useState<OrderPollingState>(initialOrderState);

    const orderStateRef = useRef<OrderPollingState>(initialOrderState);
    const hasNotifiedReadyRef = useRef(false);

    const onStatusChangeRef = useLatest(onStatusChange);
    const onReadyRef = useLatest(onReady);

    const updateOrderState = useCallback((nextState: OrderPollingState) => {
        orderStateRef.current = nextState;
        setOrderState(nextState);
    }, []);

    const fetcher = useCallback(async (): Promise<PollResult> => {
        try {
            const endpoint = ENDPOINTS.customer.orders.status(orderId);
            const { data: response, error } = await api.get<OrderStatusResponse>(endpoint, {
                suppressGlobalLoading: true,
            });

            if (error || !response) {
                throw new Error(error ?? "ステータスの取得に失敗しました");
            }

            const newData = response.data;
            const prevStatus = orderStateRef.current.status;
            const nextOrderState: OrderPollingState = {
                status: newData.status,
                statusLabel: newData.status_label,
                isTerminal: newData.is_terminal,
                readyAt: newData.ready_at,
            };

            updateOrderState(nextOrderState);

            // ステータス変更コールバック
            if (newData.status !== prevStatus && onStatusChangeRef.current) {
                onStatusChangeRef.current(newData.status, prevStatus);
            }

            // 準備完了コールバック（一度だけ通知）
            if (newData.status === "ready" && !hasNotifiedReadyRef.current) {
                hasNotifiedReadyRef.current = true;
                onReadyRef.current?.();
            }

            return {
                shouldContinue: !shouldStopPolling(nextOrderState.status, nextOrderState.isTerminal),
            };
        } catch {
            return { shouldContinue: true, errored: true };
        }
    }, [orderId, updateOrderState, onStatusChangeRef, onReadyRef]);

    usePollingTimer({
        fetcher,
        baseIntervalMs: intervalMs,
        enabled: enabled && !shouldStopPolling(initialStatus, false),
        pauseWhenHidden: true,
    });

    return {
        status: orderState.status,
        statusLabel: orderState.statusLabel,
        isReady: orderState.status === "ready",
        isTerminal: orderState.isTerminal,
        readyAt: orderState.readyAt,
    };
}
