import { useCallback, useEffect, useRef } from "react";
import { useLatest } from "./useLatest";

export interface PollResult {
    // 次回ポーリングを継続するか。終端到達時は false を返してタイマーを停止する。
    shouldContinue: boolean;
    // 失敗時に true を渡すとバックオフ間隔へ切り替える。
    errored?: boolean;
}

export interface UsePollingTimerOptions {
    // ポーリングのデータ取得関数。終端判定はこの関数の戻り値で行う。
    fetcher: () => Promise<PollResult>;
    // 通常時のポーリング間隔 (ms)。
    baseIntervalMs: number;
    // バックオフ最大間隔 (ms)。
    maxIntervalMs?: number;
    // ポーリング起動条件。false の間は何もしない。
    enabled: boolean;
    // タブ非表示時にタイマーを停止するか。
    pauseWhenHidden?: boolean;
}

export interface UsePollingTimerReturn {
    // 手動再試行。バックオフを解除して即時フェッチを行い、その後通常スケジュールを再開する。
    refresh: () => Promise<void>;
}

const DEFAULT_MAX_INTERVAL_MS = 60000;

// 注文ステータス・KDS のポーリング骨格を共通化する hook。
// 終端判定とペイロード処理は呼び出し側 fetcher に委ね、ここではタイマー・バックオフ・可視状態のみを担う。
export function usePollingTimer(options: UsePollingTimerOptions): UsePollingTimerReturn {
    const {
        baseIntervalMs,
        maxIntervalMs = DEFAULT_MAX_INTERVAL_MS,
        enabled,
        pauseWhenHidden = true,
    } = options;

    // タイマー実行時に最新の fetcher を呼べるよう参照を固定する。
    const fetcherRef = useLatest(options.fetcher);

    const isActiveRef = useRef(true);
    const timerIdRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const errorCountRef = useRef(0);
    const currentIntervalRef = useRef(baseIntervalMs);
    const isTabVisibleRef = useRef(true);
    const shouldStopRef = useRef(false);

    // 連続失敗ほど待機時間を伸ばし、障害中の API 集中を避ける。
    const calculateBackoff = useCallback(
        (errorCount: number): number => Math.min(baseIntervalMs * Math.pow(2, errorCount), maxIntervalMs),
        [baseIntervalMs, maxIntervalMs],
    );

    // 再帰呼び出しを安全に行うため scheduleNextPoll は ref 経由で参照する。
    const scheduleNextPollRef = useRef<(() => void) | null>(null);

    const runFetcher = useCallback(async (): Promise<void> => {
        if (!isActiveRef.current) return;

        try {
            const result = await fetcherRef.current();
            if (!isActiveRef.current) return;

            if (result.errored) {
                errorCountRef.current += 1;
                currentIntervalRef.current = calculateBackoff(errorCountRef.current);
            } else {
                // 一度成功したらバックオフを解除して通常間隔へ戻す。
                errorCountRef.current = 0;
                currentIntervalRef.current = baseIntervalMs;
            }

            if (!result.shouldContinue) {
                shouldStopRef.current = true;
            }
        } catch {
            if (!isActiveRef.current) return;

            errorCountRef.current += 1;
            currentIntervalRef.current = calculateBackoff(errorCountRef.current);
        }
    }, [fetcherRef, baseIntervalMs, calculateBackoff]);

    const scheduleNextPoll = useCallback(() => {
        if (!isActiveRef.current) return;
        if (shouldStopRef.current) return;
        if (pauseWhenHidden && !isTabVisibleRef.current) return;

        // 多重タイマーで同時実行しないよう前回予約を必ず破棄する。
        if (timerIdRef.current) {
            clearTimeout(timerIdRef.current);
            timerIdRef.current = null;
        }

        timerIdRef.current = setTimeout(() => {
            void runFetcher().finally(() => {
                scheduleNextPollRef.current?.();
            });
        }, currentIntervalRef.current);
    }, [pauseWhenHidden, runFetcher]);

    scheduleNextPollRef.current = scheduleNextPoll;

    useEffect(() => {
        if (!enabled) return;

        isActiveRef.current = true;
        shouldStopRef.current = false;

        // 表示直後に古い情報を見せないため、初回は待たずに同期を走らせる。
        void runFetcher().finally(() => {
            scheduleNextPollRef.current?.();
        });

        return () => {
            // アンマウント後の不要なタイマー発火を防ぐ。
            isActiveRef.current = false;
            if (timerIdRef.current) {
                clearTimeout(timerIdRef.current);
                timerIdRef.current = null;
            }
        };
    }, [enabled, runFetcher]);

    useEffect(() => {
        if (!enabled || !pauseWhenHidden) return;

        const handleVisibilityChange = () => {
            const isVisible = document.visibilityState === "visible";
            isTabVisibleRef.current = isVisible;

            if (isVisible) {
                // 非表示中の更新を取りこぼさないよう、復帰時は即時同期する。
                void runFetcher().finally(() => {
                    scheduleNextPollRef.current?.();
                });
            } else if (timerIdRef.current) {
                // バックグラウンドで無駄な実行を続けないためタイマーを止める。
                clearTimeout(timerIdRef.current);
                timerIdRef.current = null;
            }
        };

        document.addEventListener("visibilitychange", handleVisibilityChange);
        return () => {
            document.removeEventListener("visibilitychange", handleVisibilityChange);
        };
    }, [enabled, pauseWhenHidden, runFetcher]);

    // 手動再試行時はバックオフを解除し、操作意図を優先して即時再取得する。
    const refresh = useCallback(async () => {
        errorCountRef.current = 0;
        currentIntervalRef.current = baseIntervalMs;
        shouldStopRef.current = false;
        await runFetcher();
        scheduleNextPollRef.current?.();
    }, [baseIntervalMs, runFetcher]);

    return { refresh };
}
