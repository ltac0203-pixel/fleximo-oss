import { useCallback, useRef } from "react";
import { api, ENDPOINTS } from "@/api";
import { KdsOrder, KdsStatusUpdateTarget } from "@/types";
import { normalizeErrorMessage } from "@/Utils/errorHelpers";
import { logger } from "@/Utils/logger";
import { useLatest } from "./useLatest";
import {
    KdsApiOrder,
    PendingStatusUpdate,
    STATUS_LABELS,
    STATUS_SYNC_GUARD_TTL_MS,
    upsertActiveOrder,
} from "./kdsOrderHelpers";

interface UseKdsOrderActionsParams {
    ordersRef: React.MutableRefObject<KdsOrder[]>;
    setOrders: React.Dispatch<React.SetStateAction<KdsOrder[]>>;
    pendingStatusUpdatesRef: React.MutableRefObject<Map<number, PendingStatusUpdate>>;
    isActiveRef: React.MutableRefObject<boolean>;
    fetchOrders: () => Promise<void>;
    onStatusUpdateError?: (orderId: number, error: Error) => void;
}

export interface UseKdsOrderActionsReturn {
    updateOrderStatus: (orderId: number, newStatus: KdsStatusUpdateTarget) => Promise<void>;
}

export function useKdsOrderActions({
    ordersRef,
    setOrders,
    pendingStatusUpdatesRef,
    isActiveRef,
    fetchOrders,
    onStatusUpdateError,
}: UseKdsOrderActionsParams): UseKdsOrderActionsReturn {
    const onStatusUpdateErrorRef = useLatest(onStatusUpdateError);
    // 楽観更新でボタンラベルが即切り替わるため、同一注文への PATCH 連打で
    // 想定外の自動遷移 (accepted → in_progress → ready → completed) が
    // 走らないよう in-flight を注文単位で直列化する。
    const updatingOrderIdsRef = useRef<Set<number>>(new Set());

    // 操作体感を優先するため先にUIへ反映し、失敗時のみ巻き戻す。
    const updateOrderStatus = useCallback(
        async (orderId: number, newStatus: KdsStatusUpdateTarget) => {
            if (!isActiveRef.current) {
                return;
            }

            if (updatingOrderIdsRef.current.has(orderId)) {
                return;
            }
            updatingOrderIdsRef.current.add(orderId);

            // 書き戻しに失敗した場合でも確実に復元できるよう更新前を保持する。
            const previousOrders = [...ordersRef.current];

            // ネットワーク待ち中の操作遅延感を減らすため、ローカルを先に更新する。
            if (newStatus === "completed" || newStatus === "cancelled") {
                // 完了・キャンセル済みを残すと作業対象の視認性を下げるため即時に一覧から外す。
                setOrders((prev) => prev.filter((order) => order.id !== orderId));
            } else {
                // 完了以外は行を維持し、カード位置のジャンプを避ける。
                setOrders((prev) =>
                    prev.map((order) =>
                        order.id === orderId
                            ? {
                                  ...order,
                                  status: newStatus,
                                  status_label: STATUS_LABELS[newStatus] ?? newStatus,
                              }
                            : order,
                    ),
                );
            }

            try {
                const { data: updateResponse, error: updateError } = await api.patch<{
                    data: KdsApiOrder;
                    message?: string;
                }>(ENDPOINTS.tenant.kds.orderStatus(orderId), {
                    status: newStatus,
                });

                if (updateError) {
                    throw new Error(updateError);
                }

                if (!isActiveRef.current) {
                    return;
                }

                // 更新直後の古い差分で巻き戻らないよう、短時間だけ期待状態を保持する。
                const expectedStatus = updateResponse?.data?.status ?? newStatus;
                pendingStatusUpdatesRef.current.set(orderId, {
                    expectedStatus,
                    expiresAt: Date.now() + STATUS_SYNC_GUARD_TTL_MS,
                });

                // PATCHレスポンスを即時反映し、副作用はバックグラウンド同期で追従させる。
                if (updateResponse?.data) {
                    setOrders((prev) => upsertActiveOrder(prev, updateResponse.data));
                }

                void fetchOrders();
            } catch (error) {
                if (!isActiveRef.current) {
                    throw error;
                }

                // 楽観更新の失敗は業務影響が大きいため、原因追跡用の文脈を残す。
                logger.error("KDS status update failed", error, {
                    orderId,
                    newStatus,
                });

                // UIと実データの乖離を長引かせないよう即座にロールバックする。
                setOrders(previousOrders);
                pendingStatusUpdatesRef.current.delete(orderId);

                // 呼び出し元でトースト等の共通UXに載せられるよう通知する。
                if (onStatusUpdateErrorRef.current) {
                    const fallbackMessage = "ステータス更新に失敗しました";
                    const rawMessage = error instanceof Error ? error.message : fallbackMessage;
                    const normalizedMessage = normalizeErrorMessage(rawMessage, fallbackMessage);
                    onStatusUpdateErrorRef.current(orderId, new Error(normalizedMessage));
                }

                throw error;
            } finally {
                updatingOrderIdsRef.current.delete(orderId);
            }
        },
        [ordersRef, setOrders, pendingStatusUpdatesRef, isActiveRef, fetchOrders, onStatusUpdateErrorRef],
    );

    return {
        updateOrderStatus,
    };
}
