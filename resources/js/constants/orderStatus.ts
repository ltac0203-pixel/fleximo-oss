import type { OrderStatusValue } from "@/types";

// 注文ステータスの意味側ラベル。バックエンド `OrderStatus::label()` と同義の文言を保つ。
// KDS では `KDS_STATUS_META.kdsHeading` で UX 文脈別の表記（paid → 「新規注文」）を扱う。
export const ORDER_STATUS_LABELS: Record<OrderStatusValue, string> = {
    pending_payment: "決済待ち",
    paid: "決済完了",
    accepted: "受付済み",
    in_progress: "調理中",
    ready: "準備完了",
    completed: "完了",
    cancelled: "キャンセル",
    payment_failed: "決済失敗",
    refunded: "返金済み",
};

// クライアント側で「これ以上ステータス変化を待たなくてよい」と判断できる状態の集合。
// OrderReadyNotifier の初期マウント時のポーリング起動要否に利用する。
// サーバ側が返す is_terminal とは別軸（用途・判定軸が異なるため独立して定義する）。
export const POLLING_INACTIVE_ORDER_STATUSES = [
    "completed",
    "cancelled",
    "payment_failed",
    "refunded",
] as const satisfies readonly OrderStatusValue[];

export const CANCELLED_ORDER_STATUSES = ["cancelled", "refunded"] as const satisfies readonly OrderStatusValue[];

export const PAYMENT_FAILED_ORDER_STATUSES = ["payment_failed"] as const satisfies readonly OrderStatusValue[];

export function isPollingInactiveOrderStatus(status: OrderStatusValue): boolean {
    return (POLLING_INACTIVE_ORDER_STATUSES as readonly OrderStatusValue[]).includes(status);
}

export function isCancelledOrderStatus(status: OrderStatusValue): boolean {
    return (CANCELLED_ORDER_STATUSES as readonly OrderStatusValue[]).includes(status);
}

export function isPaymentFailedOrderStatus(status: OrderStatusValue): boolean {
    return (PAYMENT_FAILED_ORDER_STATUSES as readonly OrderStatusValue[]).includes(status);
}
