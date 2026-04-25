import {
    KdsOrder,
    KdsOrderStatus,
    KdsStatusUpdateTarget,
} from "@/types";
import { ORDER_STATUS_LABELS } from "@/constants/orderStatus";

export type KdsOrderApiStatus = KdsOrderStatus | "completed" | "cancelled";
export type KdsApiOrder = Omit<KdsOrder, "status"> & { status: KdsOrderApiStatus };
export type PendingStatusUpdate = {
    expectedStatus: KdsStatusUpdateTarget;
    expiresAt: number;
};

export const ACTIVE_KDS_ORDER_STATUSES: readonly KdsOrderStatus[] = ["paid", "accepted", "in_progress", "ready"];

export function isActiveKdsOrderStatus(status: KdsOrderApiStatus): status is KdsOrderStatus {
    return ACTIVE_KDS_ORDER_STATUSES.includes(status as KdsOrderStatus);
}

export function isActiveKdsOrder(order: KdsApiOrder): order is KdsOrder {
    return isActiveKdsOrderStatus(order.status);
}

// 楽観的更新時の `status_label` を意味側ラベルから引いて、API レスポンスから独立させる。
// `KDS_STATUS_META.kdsHeading` (UI 用) ではなく `ORDER_STATUS_LABELS` (意味用) を採用するのは
// レスポンスの `status_label` がバックエンド `OrderStatus::label()` と整合する場所だから。
export const STATUS_LABELS: Record<KdsStatusUpdateTarget, string> = {
    paid: ORDER_STATUS_LABELS.paid,
    accepted: ORDER_STATUS_LABELS.accepted,
    in_progress: ORDER_STATUS_LABELS.in_progress,
    ready: ORDER_STATUS_LABELS.ready,
    completed: ORDER_STATUS_LABELS.completed,
    cancelled: ORDER_STATUS_LABELS.cancelled,
};

// 運用時に「鮮度」と「API負荷」のバランスを調整できるよう既定値を固定する。
export const DEFAULT_POLLING_INTERVAL = 3000;
export const STATUS_SYNC_GUARD_TTL_MS = 5000;

// 差分ポーリング時に既存状態へ更新分だけ適用し、再描画コストを抑える。
// completed をここで除外して、完了済み注文がKDSに残り続ける不整合を防ぐ。
export function mergeOrders(existing: KdsOrder[], incoming: KdsApiOrder[]): { merged: KdsOrder[]; newOrders: KdsOrder[] } {
    const existingIds = new Set(existing.map((o) => o.id));
    const newOrders: KdsOrder[] = [];

    // ID索引を作っておくことで、差分件数が増えても探索を線形に抑える。
    const existingMap = new Map(existing.map((o) => [o.id, o]));

    for (const order of incoming) {
        // 完了済みは作業対象から外すため、到着時点で一覧から除外してサーバー状態へ追従させる。
        if (!isActiveKdsOrder(order)) {
            existingMap.delete(order.id);
            continue;
        }

        if (!existingIds.has(order.id)) {
            // 通知は新規到着分だけに限定し、既存更新で重複通知しないようにする。
            newOrders.push(order);
        }

        // 遅延到着データがあっても最終状態へ収束させるため、常に最新で上書きする。
        existingMap.set(order.id, order);
    }

    return {
        merged: Array.from(existingMap.values()),
        newOrders,
    };
}

export function upsertActiveOrder(existing: KdsOrder[], incoming: KdsApiOrder): KdsOrder[] {
    if (!isActiveKdsOrder(incoming)) {
        return existing.filter((order) => order.id !== incoming.id);
    }

    let found = false;
    const next = existing.map((order) => {
        if (order.id !== incoming.id) {
            return order;
        }

        found = true;
        return incoming;
    });

    if (!found) {
        next.push(incoming);
    }

    return next;
}

export function shouldIgnoreIncomingOrder(order: KdsApiOrder, expectedStatus: KdsStatusUpdateTarget): boolean {
    if (expectedStatus === "completed" || expectedStatus === "cancelled") {
        return order.status !== expectedStatus;
    }

    return order.status !== expectedStatus;
}
