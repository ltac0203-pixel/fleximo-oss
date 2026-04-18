import { useState, useCallback, memo } from "react";
import { KdsOrder, KdsStatusUpdateTarget } from "@/types";
import { KDS_STATUS_META } from "@/Utils/kdsHelpers";
import { useKdsStatusUpdateHandlers } from "@/Hooks/useKdsStatusUpdateHandlers";
import { useLongPress } from "@/Hooks/useLongPress";
import StatusBadge from "./StatusBadge";
import ElapsedTime from "./ElapsedTime";
import OrderItemRow from "./OrderItemRow";
import StatusActionButton, { CompleteButton } from "./StatusActionButton";
import OrderCardExpanded from "./OrderCardExpanded";

interface KdsGridCardProps {
    order: KdsOrder;
    onStatusUpdate: (orderId: number, newStatus: KdsStatusUpdateTarget) => void;
}

export default memo(function KdsGridCard({ order, onStatusUpdate }: KdsGridCardProps) {
    const [isExpanded, setIsExpanded] = useState(false);
    const [isConfirmingReject, setIsConfirmingReject] = useState(false);

    const meta = KDS_STATUS_META[order.status];

    const handleLongPress = useCallback(() => {
        if (order.status === "accepted") {
            onStatusUpdate(order.id, "in_progress");
        }
        setIsExpanded(true);
    }, [order.status, order.id, onStatusUpdate]);

    const longPressHandlers = useLongPress({
        onLongPress: handleLongPress,
    });

    const { handleStatusUpdate, handleComplete } = useKdsStatusUpdateHandlers({
        orderId: order.id,
        onStatusUpdate,
    });

    const handleCloseExpanded = useCallback(() => {
        setIsExpanded(false);
    }, []);

    // paid 用のアクション
    const handleAccept = useCallback(() => {
        navigator.vibrate?.(50);
        onStatusUpdate(order.id, "accepted");
    }, [order.id, onStatusUpdate]);

    const handleRejectClick = useCallback(() => {
        setIsConfirmingReject(true);
    }, []);

    const handleRejectConfirm = useCallback(() => {
        navigator.vibrate?.(50);
        onStatusUpdate(order.id, "cancelled");
        setIsConfirmingReject(false);
    }, [order.id, onStatusUpdate]);

    const handleRejectCancel = useCallback(() => {
        setIsConfirmingReject(false);
    }, []);

    const warningClass = order.is_warning ? "border-l-red-500 bg-red-50" : "";

    return (
        <>
            <div
                className={`
                    border border-edge bg-white shadow-sm
                    border-l-4 ${warningClass || meta.cardBorderClass}
                    select-none transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5
                `}
                {...longPressHandlers}
            >
                {/* ヘッダー: ステータスバッジ + 経過時間 */}
                <div className="flex items-center justify-between px-4 pt-3 pb-1">
                    <StatusBadge status={order.status} />
                    <ElapsedTime display={order.elapsed_display} isWarning={order.is_warning} />
                </div>

                {/* 注文番号 */}
                <div className="px-4 pb-2">
                    <span className="text-xl font-bold text-ink font-mono">
                        {order.order_code}
                    </span>
                </div>

                {/* 注文内容 */}
                <div className="px-4 pb-3 border-t border-surface-dim pt-2">
                    <div className="space-y-1">
                        {order.items.map((item) => (
                            <OrderItemRow key={item.id} item={item} />
                        ))}
                    </div>
                </div>

                {/* アクションボタン */}
                <div className="px-4 pb-3">
                    {order.status === "paid" && (
                        isConfirmingReject ? (
                            <div className="space-y-2">
                                <p className="text-sm text-red-600 font-medium text-center">
                                    この注文を却下しますか？
                                </p>
                                <div className="flex gap-2">
                                    <button
                                        onClick={handleRejectCancel}
                                        className="flex-1 py-2 text-sm font-medium text-ink-light bg-white border border-edge-strong hover:bg-surface"
                                    >
                                        戻る
                                    </button>
                                    <button
                                        onClick={handleRejectConfirm}
                                        className="flex-1 py-2 text-sm font-medium text-white bg-red-600 border border-red-500 hover:bg-red-700"
                                    >
                                        却下する
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <div className="flex gap-2">
                                <button
                                    onClick={handleRejectClick}
                                    className="flex-1 py-2 text-sm font-medium text-red-600 bg-white border border-red-400 hover:bg-red-50"
                                >
                                    却下
                                </button>
                                <button
                                    onClick={handleAccept}
                                    className="flex-1 py-2 text-sm font-medium text-white bg-emerald-600 border border-emerald-500 hover:bg-emerald-700"
                                >
                                    受付
                                </button>
                            </div>
                        )
                    )}

                    {order.status === "accepted" && (
                        <StatusActionButton currentStatus={order.status} onStatusUpdate={handleStatusUpdate} />
                    )}

                    {order.status === "in_progress" && (
                        <StatusActionButton currentStatus={order.status} onStatusUpdate={handleStatusUpdate} />
                    )}

                    {order.status === "ready" && (
                        <CompleteButton onComplete={handleComplete} />
                    )}
                </div>
            </div>

            {isExpanded && (
                <OrderCardExpanded
                    order={order}
                    onClose={handleCloseExpanded}
                    onStatusUpdate={onStatusUpdate}
                />
            )}
        </>
    );
});
