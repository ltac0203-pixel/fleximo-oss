import { useState, useEffect, useCallback, useRef } from "react";
import { api } from "@/api";
import { ENDPOINTS } from "@/api/endpoints";
import { OrderStatusValue } from "@/types";
import { useLatest } from "./useLatest";

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
const MAX_BACKOFF_INTERVAL = 60000;

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

    const isActiveRef = useRef(true);
    const timerIdRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const errorCountRef = useRef(0);
    const currentIntervalRef = useRef(intervalMs);
    const hasNotifiedReadyRef = useRef(false);
    const isTabVisible = useRef(true);
    const orderStateRef = useRef<OrderPollingState>(initialOrderState);

    const onStatusChangeRef = useLatest(onStatusChange);
    const onReadyRef = useLatest(onReady);

    const fetchOrdersRef = useRef<(() => Promise<void>) | null>(null);
    const scheduleNextPollRef = useRef<(() => void) | null>(null);

    const updateOrderState = useCallback((nextState: OrderPollingState) => {
        orderStateRef.current = nextState;
        setOrderState(nextState);
    }, []);

    const calculateBackoffInterval = useCallback(
        (errorCount: number): number => {
            const backoffInterval = intervalMs * Math.pow(2, errorCount);
            return Math.min(backoffInterval, MAX_BACKOFF_INTERVAL);
        },
        [intervalMs],
    );

    const fetchStatus = useCallback(async () => {
        if (!isActiveRef.current) return;

        try {
            const endpoint = ENDPOINTS.customer.orders.status(orderId);
            const { data: response, error } = await api.get<OrderStatusResponse>(endpoint, {
                suppressGlobalLoading: true,
            });

            if (!isActiveRef.current) return;

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

            // 終端状態ならポーリング停止
            if (shouldStopPolling(nextOrderState.status, nextOrderState.isTerminal)) {
                return;
            }

            // エラーカウントリセット
            errorCountRef.current = 0;
            currentIntervalRef.current = intervalMs;
        } catch {
            if (!isActiveRef.current) return;

            errorCountRef.current += 1;
            currentIntervalRef.current = calculateBackoffInterval(errorCountRef.current);
        }
    }, [orderId, intervalMs, calculateBackoffInterval, onStatusChangeRef, onReadyRef, updateOrderState]);

    fetchOrdersRef.current = fetchStatus;

    const scheduleNextPoll = useCallback(() => {
        if (!isActiveRef.current) return;
        if (!isTabVisible.current) return;

        if (timerIdRef.current) {
            clearTimeout(timerIdRef.current);
            timerIdRef.current = null;
        }

        timerIdRef.current = setTimeout(() => {
            if (!isActiveRef.current) return;

            void fetchOrdersRef.current?.().finally(() => {
                if (!isActiveRef.current) return;

                // 終端状態なら次のポーリングをスケジュールしない
                const currentOrderState = orderStateRef.current;
                if (shouldStopPolling(currentOrderState.status, currentOrderState.isTerminal)) return;

                scheduleNextPollRef.current?.();
            });
        }, currentIntervalRef.current);
    }, []);

    scheduleNextPollRef.current = scheduleNextPoll;

    // ポーリング開始
    useEffect(() => {
        if (!enabled || shouldStopPolling(initialStatus, false)) return;

        isActiveRef.current = true;

        void fetchOrdersRef.current?.().finally(() => {
            if (!isActiveRef.current) return;
            scheduleNextPollRef.current?.();
        });

        return () => {
            isActiveRef.current = false;
            if (timerIdRef.current) {
                clearTimeout(timerIdRef.current);
                timerIdRef.current = null;
            }
        };
    }, [enabled, initialStatus]);

    // タブ可視状態の監視
    useEffect(() => {
        if (!enabled) return;

        const handleVisibilityChange = () => {
            const isVisible = document.visibilityState === "visible";
            isTabVisible.current = isVisible;

            if (isVisible) {
                void fetchOrdersRef.current?.().finally(() => {
                    if (!isActiveRef.current) return;
                    scheduleNextPollRef.current?.();
                });
            } else {
                if (timerIdRef.current) {
                    clearTimeout(timerIdRef.current);
                    timerIdRef.current = null;
                }
            }
        };

        document.addEventListener("visibilitychange", handleVisibilityChange);
        return () => {
            document.removeEventListener("visibilitychange", handleVisibilityChange);
        };
    }, [enabled]);

    return {
        status: orderState.status,
        statusLabel: orderState.statusLabel,
        isReady: orderState.status === "ready",
        isTerminal: orderState.isTerminal,
        readyAt: orderState.readyAt,
    };
}
