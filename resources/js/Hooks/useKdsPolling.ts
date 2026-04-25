import { useState, useEffect, useCallback, useRef } from "react";
import { api, ENDPOINTS } from "@/api";
import { KdsOrder, PollingState } from "@/types";
import { normalizeErrorMessage } from "@/Utils/errorHelpers";
import { logger } from "@/Utils/logger";
import { useLatest } from "./useLatest";
import {
    KdsApiOrder,
    PendingStatusUpdate,
    DEFAULT_POLLING_INTERVAL,
    isActiveKdsOrder,
    mergeOrders,
    shouldIgnoreIncomingOrder,
} from "./kdsOrderHelpers";

interface UseKdsPollingParams {
    initialServerTime?: string;
    pollingInterval?: number;
    setOrders: React.Dispatch<React.SetStateAction<KdsOrder[]>>;
    pendingStatusUpdatesRef: React.MutableRefObject<Map<number, PendingStatusUpdate>>;
    isActiveRef: React.MutableRefObject<boolean>;
    onNewOrder?: (newOrders: KdsOrder[]) => void;
    onPollingError?: (error: Error) => void;
}

export interface UseKdsPollingReturn {
    pollingState: PollingState;
    lastUpdated: Date | null;
    lastServerTime: string | null;
    fetchOrders: () => Promise<void>;
    refresh: () => Promise<void>;
}

export function useKdsPolling({
    initialServerTime,
    pollingInterval,
    setOrders,
    pendingStatusUpdatesRef,
    isActiveRef,
    onNewOrder,
    onPollingError,
}: UseKdsPollingParams): UseKdsPollingReturn {
    // デプロイ後も頻度を調整できるよう、props未指定時は環境変数を既定値として扱う。
    const intervalMs =
        pollingInterval ?? Number(import.meta.env.VITE_KDS_POLLING_INTERVAL_MS ?? DEFAULT_POLLING_INTERVAL);
    const activeRef = isActiveRef;

    const [pollingState, setPollingState] = useState<PollingState>("idle");
    const [lastUpdated, setLastUpdated] = useState<Date | null>(null);
    const [lastServerTime, setLastServerTime] = useState<string | null>(initialServerTime ?? null);

    // 非同期コールバック内で古いクロージャを参照し続けないよう、可変参照に寄せる。
    const lastServerTimeRef = useRef<string | null>(initialServerTime ?? null);
    const onNewOrderRef = useLatest(onNewOrder);
    const onPollingErrorRef = useLatest(onPollingError);

    // initialServerTime がある場合は初期データをInertia経由で取得済みなので、差分フェッチから開始する。
    const isFirstFetch = useRef(!initialServerTime);

    // 非表示タブへの無駄なポーリングを止めるため可視状態を保持する。
    const isTabVisible = useRef(true);

    // 再帰setTimeoutを安全に停止できるよう、タイマーIDを保持する。
    const timerIdRef = useRef<NodeJS.Timeout | null>(null);

    // 定期フルリフレッシュのため、ポーリング回数をカウントする。
    const pollCountRef = useRef(0);
    const FULL_REFRESH_INTERVAL = 30;

    // 一時障害時の過剰リトライを避けるため、連続失敗回数を保持する。
    const errorCountRef = useRef(0);
    const hasNotifiedPollingErrorRef = useRef(false);

    // バックオフで変化した間隔を次回スケジュールへ反映する。
    const currentIntervalRef = useRef(intervalMs);

    // タイマー実行時にも最新ロジックを呼べるよう関数参照を固定する。
    const fetchOrdersRef = useRef<(() => Promise<void>) | null>(null);
    const scheduleNextPollRef = useRef<(() => void) | null>(null);

    // 設定変更後の新しい間隔を即座に使えるよう、最新値を参照化する。
    const intervalMsRef = useLatest(intervalMs);

    // 回復前に再試行を打ちすぎないため、失敗回数に応じて待機時間を伸ばす。
    const calculateBackoffInterval = useCallback((errorCount: number, baseInterval: number): number => {
        const maxBackoff = Number(import.meta.env.VITE_KDS_POLLING_BACKOFF_MAX_MS ?? 60000);

        // 指数関数で伸ばして短時間の連続失敗時にAPI集中を防ぐ。
        const backoffInterval = baseInterval * Math.pow(2, errorCount);
        return Math.min(backoffInterval, maxBackoff);
    }, []);

    const pruneExpiredPendingStatusUpdates = useCallback((now: number) => {
        const pendingUpdates = pendingStatusUpdatesRef.current;

        for (const [orderId, pendingUpdate] of pendingUpdates.entries()) {
            if (pendingUpdate.expiresAt <= now) {
                pendingUpdates.delete(orderId);
            }
        }
    }, [pendingStatusUpdatesRef]);

    const guardIncomingOrdersWithPendingUpdates = useCallback(
        (incoming: KdsApiOrder[]): { guardedIncoming: KdsApiOrder[]; ignoredStaleIncoming: boolean } => {
            const now = Date.now();
            const pendingUpdates = pendingStatusUpdatesRef.current;
            const guardedIncoming: KdsApiOrder[] = [];
            let ignoredStaleIncoming = false;

            pruneExpiredPendingStatusUpdates(now);

            for (const order of incoming) {
                const pendingUpdate = pendingUpdates.get(order.id);
                if (!pendingUpdate) {
                    guardedIncoming.push(order);
                    continue;
                }

                if (shouldIgnoreIncomingOrder(order, pendingUpdate.expectedStatus)) {
                    ignoredStaleIncoming = true;
                    continue;
                }

                // 期待状態に追いついたデータを受け取ったらガードを解除する。
                pendingUpdates.delete(order.id);
                guardedIncoming.push(order);
            }

            return {
                guardedIncoming,
                ignoredStaleIncoming,
            };
        },
        [pendingStatusUpdatesRef, pruneExpiredPendingStatusUpdates],
    );

    // updated_since を使った差分取得を優先し、KDS端末の通信量を抑える。
    const fetchOrders = useCallback(async () => {
        if (!activeRef.current) {
            return;
        }

        try {
            // 定期フルリフレッシュ: 30回ごとに全件取得して取りこぼしを補正する。
            pollCountRef.current += 1;
            const isFullRefresh = pollCountRef.current >= FULL_REFRESH_INTERVAL;
            if (isFullRefresh) {
                pollCountRef.current = 0;
            }

            const params: Record<string, string> = {};
            if (!isFullRefresh && lastServerTimeRef.current) {
                params.updated_since = lastServerTimeRef.current;
            }

            const query = new URLSearchParams(params).toString();
            const endpoint = query ? `${ENDPOINTS.tenant.kds.orders}?${query}` : ENDPOINTS.tenant.kds.orders;

            const { data: response, error: fetchError } = await api.get<{
                data: KdsApiOrder[];
                meta?: { server_time?: string };
            }>(endpoint, {
                // ポーリングは背面処理のため、全画面ローディングで操作を止めない。
                suppressGlobalLoading: true,
            });

            if (!activeRef.current) {
                return;
            }

            if (fetchError || !response) {
                throw new Error(normalizeErrorMessage(fetchError, "注文の取得に失敗しました"));
            }

            const incoming = response.data;
            const serverTime = response.meta?.server_time;
            const { guardedIncoming, ignoredStaleIncoming } = guardIncomingOrdersWithPendingUpdates(incoming);

            // フルリフレッシュ時はサーバー状態を正として全件置換する。
            // 初回または差分なし時も全件同期する。
            if (isFullRefresh || isFirstFetch.current || !lastServerTimeRef.current) {
                setOrders(guardedIncoming.filter(isActiveKdsOrder));
                isFirstFetch.current = false;
            } else if (guardedIncoming.length === 0) {
                // 差分なし — state更新をスキップして再描画を防ぐ
            } else {
                setOrders((prev) => {
                    const { merged, newOrders } = mergeOrders(prev, guardedIncoming);

                    // 新規到着だけ通知し、更新分で重複して通知されるのを防ぐ。
                    if (newOrders.length > 0 && onNewOrderRef.current) {
                        onNewOrderRef.current(newOrders);
                    }

                    return merged;
                });
            }

            // 端末時計のズレを避けるため、差分基準は常にサーバー時刻に揃える。
            if (serverTime && !ignoredStaleIncoming) {
                lastServerTimeRef.current = serverTime;
                setLastServerTime(serverTime);
            }

            // 一度成功したらバックオフを解除して通常間隔へ戻す。
            if (errorCountRef.current > 0) {
                setPollingState("idle");
            }
            errorCountRef.current = 0;
            currentIntervalRef.current = intervalMsRef.current;
            hasNotifiedPollingErrorRef.current = false;

            setLastUpdated(new Date());
        } catch (error) {
            if (!activeRef.current) {
                return;
            }

            // 一時障害を前提に詳細を記録し、次回リトライ判断に使えるようにする。
            logger.error("KDS order polling failed", error, {
                intervalMs: intervalMsRef.current,
                lastServerTime: lastServerTimeRef.current,
            });

            // 連続失敗ほど待機時間を延ばし、障害中のリクエスト集中を避ける。
            errorCountRef.current += 1;
            currentIntervalRef.current = calculateBackoffInterval(errorCountRef.current, intervalMsRef.current);

            if (!hasNotifiedPollingErrorRef.current) {
                const fallbackMessage = "注文の取得に失敗しました";
                const rawMessage = error instanceof Error ? error.message : fallbackMessage;
                const normalizedMessage = normalizeErrorMessage(rawMessage, fallbackMessage);
                if (onPollingErrorRef.current) {
                    onPollingErrorRef.current(new Error(normalizedMessage));
                }
                hasNotifiedPollingErrorRef.current = true;
            }

            setPollingState("error");
        }
    }, [
        activeRef,
        setOrders,
        calculateBackoffInterval,
        guardIncomingOrdersWithPendingUpdates,
        intervalMsRef,
        onNewOrderRef,
        onPollingErrorRef,
    ]);

    // タイマーから古いクロージャを呼ばないよう、参照先を都度更新する。
    fetchOrdersRef.current = fetchOrders;

    // fetch完了後に次回を予約し、通信遅延時のリクエスト重複を防ぐ。
    const scheduleNextPoll = useCallback(() => {
        if (!activeRef.current) {
            return;
        }

        const pauseWhenHidden = import.meta.env.VITE_KDS_POLLING_PAUSE_WHEN_HIDDEN === "true";

        // 操作者が見ていない間はポーリングを止めてリソースを節約する。
        if (pauseWhenHidden && !isTabVisible.current) {
            return;
        }

        // 多重タイマーで同時実行しないよう前回予約を必ず破棄する。
        if (timerIdRef.current) {
            clearTimeout(timerIdRef.current);
            timerIdRef.current = null;
        }

        timerIdRef.current = setTimeout(() => {
            if (!activeRef.current) {
                return;
            }

            void fetchOrdersRef.current?.().finally(() => {
                if (!activeRef.current) {
                    return;
                }
                scheduleNextPollRef.current?.();
            });
        }, currentIntervalRef.current);
    }, [activeRef]);

    // finally節から最新スケジューラを呼べるよう参照を同期する。
    scheduleNextPollRef.current = scheduleNextPoll;

    // 表示直後に古い注文を見せないため、初回は待たずに同期を走らせる。
    useEffect(() => {
        activeRef.current = true;

        void fetchOrdersRef.current?.().finally(() => {
            if (!activeRef.current) {
                return;
            }
            scheduleNextPollRef.current?.();
        });

        // アンマウント後の不要なタイマー発火を防ぐ。
        return () => {
            activeRef.current = false;
            if (timerIdRef.current) {
                clearTimeout(timerIdRef.current);
                timerIdRef.current = null;
            }
        };
    }, [activeRef]);

    // 復帰直後に差分を取り込めるよう、可視状態とポーリングを連動させる。
    useEffect(() => {
        const pauseWhenHidden = import.meta.env.VITE_KDS_POLLING_PAUSE_WHEN_HIDDEN === "true";

        if (!pauseWhenHidden) return;

        const handleVisibilityChange = () => {
            const isVisible = document.visibilityState === "visible";
            isTabVisible.current = isVisible;

            if (isVisible) {
                // 非表示中の更新を取りこぼさないよう、復帰時は即時同期する。
                void fetchOrdersRef.current?.().finally(() => {
                    if (!activeRef.current) {
                        return;
                    }
                    scheduleNextPollRef.current?.();
                });
            } else {
                // バックグラウンドで無駄な実行を続けないためタイマーを止める。
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
    }, [activeRef]);

    // 初期データ表示時にも更新時刻を示せるよう初期化しておく。
    useEffect(() => {
        setLastUpdated(new Date());
    }, []);

    // 手動再試行時はバックオフを解除し、操作意図を優先して即時再取得する。
    const refresh = useCallback(async () => {
        errorCountRef.current = 0;
        currentIntervalRef.current = intervalMsRef.current;

        await fetchOrders();
    }, [fetchOrders, intervalMsRef]);

    return {
        pollingState,
        lastUpdated,
        lastServerTime,
        fetchOrders,
        refresh,
    };
}
