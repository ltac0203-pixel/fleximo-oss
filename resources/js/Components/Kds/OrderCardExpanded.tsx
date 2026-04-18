import { KdsOrder } from "@/types";
import { KdsStatusUpdateHandler, useKdsStatusUpdateHandlers } from "@/Hooks/useKdsStatusUpdateHandlers";
import { useEffect } from "react";
import ElapsedTime from "./ElapsedTime";
import StatusActionButton, { CompleteButton } from "./StatusActionButton";

interface OrderCardExpandedProps {
    order: KdsOrder;
    onClose: () => void;
    onStatusUpdate?: KdsStatusUpdateHandler;
}

export default function OrderCardExpanded({ order, onClose, onStatusUpdate }: OrderCardExpandedProps) {
    const { handleStatusUpdate, handleComplete } = useKdsStatusUpdateHandlers({
        orderId: order.id,
        onStatusUpdate,
        onAfterUpdate: onClose,
    });

    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === "Escape") onClose();
        };
        document.addEventListener("keydown", handleKeyDown);
        return () => document.removeEventListener("keydown", handleKeyDown);
    }, [onClose]);

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-ink/40" role="dialog" aria-modal="true" onClick={onClose}>
            <div
                className="w-full max-w-md mx-4 bg-white border border-edge overflow-hidden"
                onClick={(e) => e.stopPropagation()}
            >
                {/* ヘッダー を明示し、実装意図の誤読を防ぐ。 */}
                <div className="flex items-center justify-between p-4 border-b border-edge">
                    <div className="flex items-center gap-4">
                        <span className="text-2xl font-bold text-ink font-mono">{order.order_code}</span>
                        <ElapsedTime display={order.elapsed_display} isWarning={order.is_warning} />
                    </div>
                    <button
                        onClick={onClose}
                        className="p-2 -m-2 text-muted hover:text-ink-light"
                        aria-label="閉じる"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                {/* 商品リスト を明示し、実装意図の誤読を防ぐ。 */}
                <div className="p-4 max-h-[60vh] overflow-y-auto">
                    <div className="space-y-3">
                        {order.items.map((item) => (
                            <div key={item.id} className="p-3 bg-surface border border-edge">
                                <div className="flex items-start justify-between mb-1">
                                    <span className="text-base font-medium text-ink">{item.name}</span>
                                    <span className="text-lg font-bold text-sky-600">x{item.quantity}</span>
                                </div>
                                {item.options.length > 0 && (
                                    <div className="text-sm text-muted mb-1">
                                        {item.options.map((opt) => opt.name).join(", ")}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>

                {/* アクションエリア を明示し、実装意図の誤読を防ぐ。 */}
                <div className="p-4 border-t border-edge space-y-2">
                    {onStatusUpdate && order.status !== "ready" && order.status !== "accepted" && (
                        <StatusActionButton currentStatus={order.status} onStatusUpdate={handleStatusUpdate} />
                    )}
                    {order.status === "ready" && <CompleteButton onComplete={handleComplete} />}
                </div>
            </div>
        </div>
    );
}
