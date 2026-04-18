import { useCallback, useMemo } from "react";
import KdsLayout from "@/Layouts/KdsLayout";
import StatusFilterBar from "@/Components/Kds/StatusFilterBar";
import KdsOrderGrid from "@/Components/Kds/KdsOrderGrid";
import ToastContainer from "@/Components/UI/ToastContainer";
import { useKdsOrders } from "@/Hooks/useKdsOrders";
import { useKdsStatusFilter } from "@/Hooks/useKdsStatusFilter";
import { useReadyAutoComplete } from "@/Hooks/useReadyAutoComplete";
import { useNotificationSound } from "@/Hooks/useNotificationSound";
import { useOrderPause } from "@/Hooks/useOrderPause";
import { useToast } from "@/Hooks/useToast";
import { KdsOrder, KdsOrderStatus, KdsPageProps, KdsStatusUpdateTarget } from "@/types";
import { KDS_STATUS_PRIORITY } from "@/Utils/kdsHelpers";
import { normalizeErrorMessage } from "@/Utils/errorHelpers";

export default function KdsIndex({ orders: initialOrders, businessDate, serverTime, isOrderPaused: initialPaused, readyAutoCompleteSeconds }: KdsPageProps) {
    const { playNewOrderSound } = useNotificationSound();
    const { toasts, showToast, hideToast } = useToast();
    const { isOrderPaused, isToggling, toggleOrderPause } = useOrderPause(initialPaused);
    const { activeStatuses, toggleStatus } = useKdsStatusFilter();

    const handleNewOrder = useCallback(
        (newOrders: KdsOrder[]) => {
            if (newOrders.length > 0) {
                playNewOrderSound();
            }
        },
        [playNewOrderSound],
    );

    const handleStatusUpdateError = useCallback(
        (_orderId: number, error: Error) => {
            showToast({
                type: "error",
                message: normalizeErrorMessage(error.message, "ステータス更新に失敗しました"),
            });
        },
        [showToast],
    );

    const handlePollingError = useCallback(
        (error: Error) => {
            showToast({
                type: "error",
                message: normalizeErrorMessage(error.message, "注文の取得に失敗しました"),
            });
        },
        [showToast],
    );

    const { orders, paidOrders, acceptedOrders, inProgressOrders, readyOrders, pollingState, updateOrderStatus } =
        useKdsOrders({
            initialOrders,
            initialServerTime: serverTime,
            onNewOrder: handleNewOrder,
            onStatusUpdateError: handleStatusUpdateError,
            onPollingError: handlePollingError,
        });

    const handleStatusUpdate = useCallback(
        (orderId: number, newStatus: KdsStatusUpdateTarget) => {
            void updateOrderStatus(orderId, newStatus);
        },
        [updateOrderStatus],
    );

    // フィルターに関係なく全readyオーダーで自動完了を動作させる（業務ルール）
    useReadyAutoComplete({
        readyOrders,
        autoCompleteSeconds: readyAutoCompleteSeconds,
        onAutoComplete: handleStatusUpdate,
    });

    // 各ステータスの件数
    const orderCounts = useMemo<Record<KdsOrderStatus, number>>(
        () => ({
            paid: paidOrders.length,
            accepted: acceptedOrders.length,
            in_progress: inProgressOrders.length,
            ready: readyOrders.length,
        }),
        [paidOrders, acceptedOrders, inProgressOrders, readyOrders],
    );

    // フィルター適用 + ソート（ステータス優先度順 → 同ステータス内は経過時間の長い順）
    const filteredOrders = useMemo(() => {
        const filtered = orders.filter((order) => activeStatuses.has(order.status));
        return filtered.toSorted((a, b) => {
            const priorityDiff = KDS_STATUS_PRIORITY[a.status] - KDS_STATUS_PRIORITY[b.status];
            if (priorityDiff !== 0) return priorityDiff;
            return b.elapsed_seconds - a.elapsed_seconds;
        });
    }, [orders, activeStatuses]);

    return (
        <KdsLayout
            businessDate={businessDate}
            pollingState={pollingState}
            isOrderPaused={isOrderPaused}
            isToggling={isToggling}
            onTogglePause={() => void toggleOrderPause()}
        >
            <div className="flex flex-col gap-4 h-full">
                <div className="flex-shrink-0">
                    <StatusFilterBar
                        activeStatuses={activeStatuses}
                        orderCounts={orderCounts}
                        onToggle={toggleStatus}
                    />
                </div>

                <div className="flex-1 overflow-y-auto">
                    <KdsOrderGrid
                        orders={filteredOrders}
                        onStatusUpdate={handleStatusUpdate}
                    />
                </div>
            </div>

            <ToastContainer toasts={toasts} onClose={hideToast} />
        </KdsLayout>
    );
}
