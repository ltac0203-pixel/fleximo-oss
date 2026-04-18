import { ReactNode, useEffect, useRef } from "react";
import { useOrderStatusPolling } from "@/Hooks/useOrderStatusPolling";
import { useBrowserNotification } from "@/Hooks/useBrowserNotification";
import { useNotificationSound } from "@/Hooks/useNotificationSound";
import { OrderStatusValue } from "@/types";

interface PollingState {
    status: OrderStatusValue;
    statusLabel: string;
    isReady: boolean;
    isTerminal: boolean;
    readyAt: string | null;
}

interface OrderReadyNotifierProps {
    orderId: number;
    orderCode: string;
    initialStatus: OrderStatusValue;
    initialStatusLabel: string;
    children: (polling: PollingState) => ReactNode;
}

// ポーリング不要な状態かどうかを判定する
function isInactiveStatus(status: OrderStatusValue): boolean {
    return ["completed", "cancelled", "payment_failed", "refunded"].includes(status);
}

export default function OrderReadyNotifier({
    orderId,
    orderCode,
    initialStatus,
    initialStatusLabel,
    children,
}: OrderReadyNotifierProps) {
    const { requestPermission, showNotification } = useBrowserNotification();
    const { playNewOrderSound } = useNotificationSound();
    const hasNotifiedRef = useRef(false);

    // マウント時にブラウザ通知の許可をリクエスト
    useEffect(() => {
        if (!isInactiveStatus(initialStatus)) {
            void requestPermission();
        }
    }, [initialStatus, requestPermission]);

    const polling = useOrderStatusPolling({
        orderId,
        initialStatus,
        initialStatusLabel,
        enabled: !isInactiveStatus(initialStatus),
        onReady: () => {
            if (hasNotifiedRef.current) return;
            hasNotifiedRef.current = true;

            playNewOrderSound();

            showNotification("準備完了", {
                body: `注文 #${orderCode} の準備ができました。カウンターにてお受け取りください。`,
                tag: `order-ready-${orderId}`,
            });
        },
    });

    return <>{children(polling)}</>;
}
