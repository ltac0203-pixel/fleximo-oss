import type { PageProps } from "./common";

export type KdsOrderStatus = "paid" | "accepted" | "in_progress" | "ready";

export type KdsStatusUpdateTarget = KdsOrderStatus | "completed" | "cancelled";

export type PollingState = "idle" | "polling" | "error";

export interface KdsOrderItemOption {
    name: string;
    price: number;
}

export interface KdsOrderItem {
    id: number;
    name: string;
    quantity: number;
    options: KdsOrderItemOption[];
}

export interface KdsStatusMeta {
    label: string;
    dotClass: string;
    cardBorderClass: string;
    badgeBgClass: string;
    badgeTextClass: string;
}

export interface KdsOrder {
    id: number;
    order_code: string;
    status: KdsOrderStatus;
    status_label: string;
    items: KdsOrderItem[];
    item_count: number;
    elapsed_seconds: number;
    elapsed_display: string;
    is_warning: boolean;
    paid_at: string | null;
    accepted_at: string | null;
    in_progress_at: string | null;
    ready_at: string | null;
    created_at: string;
}

export interface KdsPageProps extends PageProps {
    orders: KdsOrder[];
    businessDate: string;
    serverTime: string;
    isOrderPaused: boolean;
    readyAutoCompleteSeconds: number;
}

export interface UseKdsOrdersOptions {
    initialOrders: KdsOrder[];
    initialServerTime?: string;
    pollingInterval?: number;
    onNewOrder?: (newOrders: KdsOrder[]) => void;
    onStatusUpdateError?: (orderId: number, error: Error) => void;
    onPollingError?: (error: Error) => void;
}

export interface UseKdsOrdersReturn {
    orders: KdsOrder[];
    pollingState: PollingState;
    lastUpdated: Date | null;
    lastServerTime: string | null;
    paidOrders: KdsOrder[];
    acceptedOrders: KdsOrder[];
    inProgressOrders: KdsOrder[];
    readyOrders: KdsOrder[];
    updateOrderStatus: (orderId: number, newStatus: KdsStatusUpdateTarget) => Promise<void>;
    refresh: () => Promise<void>;
}
