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
import { usePollingTimer, type PollResult } from "./usePollingTimer";

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

const FULL_REFRESH_INTERVAL = 30;

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
    const maxBackoffMs = Number(import.meta.env.VITE_KDS_POLLING_BACKOFF_MAX_MS ?? 60000);
    const pauseWhenHidden = import.meta.env.VITE_KDS_POLLING_PAUSE_WHEN_HIDDEN === "true";
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

    // 定期フルリフレッシュのため、ポーリング回数をカウントする。
    const pollCountRef = useRef(0);

    // 同一エラーで何度も通知しないよう、復旧まで通知済みフラグを保持する。
    const hasNotifiedPollingErrorRef = useRef(false);

    const pruneExpiredPendingStatusUpdates = useCallback(
        (now: number) => {
            const pendingUpdates = pendingStatusUpdatesRef.current;

            for (const [orderId, pendingUpdate] of pendingUpdates.entries()) {
                if (pendingUpdate.expiresAt <= now) {
                    pendingUpdates.delete(orderId);
                }
            }
        },
        [pendingStatusUpdatesRef],
    );

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

            // 一度成功したらバックオフ済みステートを通常に戻し、再通知を許可する。
            setPollingState("idle");
            hasNotifiedPollingErrorRef.current = false;

            setLastUpdated(new Date());
        } catch (error) {
            if (!activeRef.current) {
                return;
            }

            // 一時障害を前提に詳細を記録し、次回リトライ判断に使えるようにする。
            logger.error("KDS order polling failed", error, {
                intervalMs,
                lastServerTime: lastServerTimeRef.current,
            });

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
            // 上位の usePollingTimer にバックオフを判断させるため例外を再送出する。
            throw error;
        }
    }, [
        activeRef,
        setOrders,
        guardIncomingOrdersWithPendingUpdates,
        intervalMs,
        onNewOrderRef,
        onPollingErrorRef,
    ]);

    // KDS は終端のない永続ループ。エラー時は usePollingTimer 側でバックオフが切り替わる。
    const fetcher = useCallback(async (): Promise<PollResult> => {
        try {
            await fetchOrders();
            return { shouldContinue: true };
        } catch {
            return { shouldContinue: true, errored: true };
        }
    }, [fetchOrders]);

    const { refresh: timerRefresh } = usePollingTimer({
        fetcher,
        baseIntervalMs: intervalMs,
        maxIntervalMs: maxBackoffMs,
        enabled: true,
        pauseWhenHidden,
    });

    // 初期データ表示時にも更新時刻を示せるよう初期化しておく。
    useEffect(() => {
        setLastUpdated(new Date());
    }, []);

    // アンマウント後の遅延 fetcher 完了から `setOrders` や `onPollingError` が呼ばれないよう、
    // 親側の isActiveRef をライフサイクルに同期させる。
    useEffect(() => {
        activeRef.current = true;
        return () => {
            activeRef.current = false;
        };
    }, [activeRef]);

    const refresh = useCallback(async () => {
        await timerRefresh();
    }, [timerRefresh]);

    return {
        pollingState,
        lastUpdated,
        lastServerTime,
        fetchOrders,
        refresh,
    };
}
