import { useEffect, useRef } from "react";
import { KdsOrder, KdsStatusUpdateTarget } from "@/types";

interface UseReadyAutoCompleteOptions {
    readyOrders: KdsOrder[];
    autoCompleteSeconds: number;
    onAutoComplete: (orderId: number, newStatus: KdsStatusUpdateTarget) => void;
}

export function useReadyAutoComplete({
    readyOrders,
    autoCompleteSeconds,
    onAutoComplete,
}: UseReadyAutoCompleteOptions): void {
    const timersRef = useRef<Map<number, NodeJS.Timeout>>(new Map());
    const onAutoCompleteRef = useRef(onAutoComplete);
    onAutoCompleteRef.current = onAutoComplete;

    useEffect(() => {
        const timers = timersRef.current;
        const currentOrderIds = new Set(readyOrders.map((o) => o.id));

        // 消えた注文のタイマーをクリア
        for (const [orderId, timer] of timers.entries()) {
            if (!currentOrderIds.has(orderId)) {
                clearTimeout(timer);
                timers.delete(orderId);
            }
        }

        // 新しい注文にタイマーをセット
        for (const order of readyOrders) {
            if (timers.has(order.id)) {
                continue;
            }

            if (!order.ready_at) {
                continue;
            }

            const readyAt = new Date(order.ready_at).getTime();
            const now = Date.now();
            const elapsedMs = now - readyAt;
            const remainingMs = autoCompleteSeconds * 1000 - elapsedMs;

            if (remainingMs <= 0) {
                onAutoCompleteRef.current(order.id, "completed");
            } else {
                const timer = setTimeout(() => {
                    timers.delete(order.id);
                    onAutoCompleteRef.current(order.id, "completed");
                }, remainingMs);
                timers.set(order.id, timer);
            }
        }
    }, [readyOrders, autoCompleteSeconds]);

    // クリーンアップで全タイマー解除
    useEffect(() => {
        const timers = timersRef.current;
        return () => {
            for (const timer of timers.values()) {
                clearTimeout(timer);
            }
            timers.clear();
        };
    }, []);
}
