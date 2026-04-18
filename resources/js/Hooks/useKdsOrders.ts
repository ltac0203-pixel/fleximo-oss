import { useState, useMemo, useRef } from "react";
import {
    KdsOrder,
    UseKdsOrdersReturn,
    UseKdsOrdersOptions,
} from "@/types";
import { useLatest } from "./useLatest";
import { PendingStatusUpdate } from "./kdsOrderHelpers";
import { useKdsPolling } from "./useKdsPolling";
import { useKdsOrderActions } from "./useKdsOrderActions";

export function useKdsOrders({
    initialOrders,
    initialServerTime,
    pollingInterval,
    onNewOrder,
    onStatusUpdateError,
    onPollingError,
}: UseKdsOrdersOptions): UseKdsOrdersReturn {
    const [orders, setOrders] = useState<KdsOrder[]>(initialOrders ?? []);
    const ordersRef = useLatest(orders);
    const pendingStatusUpdatesRef = useRef<Map<number, PendingStatusUpdate>>(new Map());
    const isActiveRef = useRef(true);

    const polling = useKdsPolling({
        initialServerTime,
        pollingInterval,
        setOrders,
        pendingStatusUpdatesRef,
        isActiveRef,
        onNewOrder,
        onPollingError,
    });

    const actions = useKdsOrderActions({
        ordersRef,
        setOrders,
        pendingStatusUpdatesRef,
        isActiveRef,
        fetchOrders: polling.fetchOrders,
        onStatusUpdateError,
    });

    // 頻繁なポーリング更新時の負荷を抑えるため、1回の走査で各ステータス配列へ振り分ける。
    const { paidOrders, acceptedOrders, inProgressOrders, readyOrders } = useMemo(() => {
        const nextPaidOrders: KdsOrder[] = [];
        const nextAcceptedOrders: KdsOrder[] = [];
        const nextInProgressOrders: KdsOrder[] = [];
        const nextReadyOrders: KdsOrder[] = [];

        for (const order of orders) {
            switch (order.status) {
                case "paid":
                    nextPaidOrders.push(order);
                    break;
                case "accepted":
                    nextAcceptedOrders.push(order);
                    break;
                case "in_progress":
                    nextInProgressOrders.push(order);
                    break;
                case "ready":
                    nextReadyOrders.push(order);
                    break;
                default:
                    break;
            }
        }

        return {
            paidOrders: nextPaidOrders,
            acceptedOrders: nextAcceptedOrders,
            inProgressOrders: nextInProgressOrders,
            readyOrders: nextReadyOrders,
        };
    }, [orders]);

    return {
        orders,
        pollingState: polling.pollingState,
        lastUpdated: polling.lastUpdated,
        lastServerTime: polling.lastServerTime,
        paidOrders,
        acceptedOrders,
        inProgressOrders,
        readyOrders,
        updateOrderStatus: actions.updateOrderStatus,
        refresh: polling.refresh,
    };
}
