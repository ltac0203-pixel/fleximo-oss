import { isCancelledOrderStatus, isPaymentFailedOrderStatus } from "@/constants/orderStatus";
import { OrderDetail, OrderStatusValue } from "@/types";

interface OrderTimelineProps {
    order: OrderDetail;
}

interface TimelineStep {
    key: keyof Pick<
        OrderDetail,
        "created_at" | "paid_at" | "accepted_at" | "in_progress_at" | "ready_at" | "completed_at" | "cancelled_at"
    >;
    label: string;
    status: OrderStatusValue;
}

const normalSteps: TimelineStep[] = [
    { key: "created_at", label: "注文", status: "pending_payment" },
    { key: "paid_at", label: "決済完了", status: "paid" },
    { key: "accepted_at", label: "受付", status: "accepted" },
    { key: "in_progress_at", label: "調理中", status: "in_progress" },
    { key: "ready_at", label: "準備完了", status: "ready" },
    { key: "completed_at", label: "完了", status: "completed" },
];

function formatTime(dateString: string | null): string {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleTimeString("ja-JP", {
        hour: "2-digit",
        minute: "2-digit",
    });
}

function getStepStatus(
    order: OrderDetail,
    step: TimelineStep,
    stepIndex: number,
    activeStepIndex: number,
): "completed" | "active" | "pending" {
    if (order[step.key]) {
        return "completed";
    }
    if (stepIndex === activeStepIndex + 1) {
        return "active";
    }
    return "pending";
}

export default function OrderTimeline({ order }: OrderTimelineProps) {
    const isCancelled = isCancelledOrderStatus(order.status);
    const isPaymentFailed = isPaymentFailedOrderStatus(order.status);

    if (isPaymentFailed) {
        return (
            <div className="bg-red-50 border border-red-200 p-4">
                <div className="flex items-center gap-2 text-red-800">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span className="font-medium">決済に失敗しました</span>
                </div>
            </div>
        );
    }

    const activeStepIndex = normalSteps.findIndex((step) => !order[step.key]) - 1;

    return (
        <div className="bg-white border border-edge p-4">
            <h3 className="text-base font-semibold text-ink mb-4">ステータス</h3>

            {isCancelled && (
                <div className="bg-red-50 border border-red-200 p-3 mb-4">
                    <div className="flex items-center gap-2 text-red-800">
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                        <span className="font-medium">{order.status === "refunded" ? "返金済み" : "キャンセル"}</span>
                        {order.cancelled_at && (
                            <span className="text-sm text-red-600 ml-auto">{formatTime(order.cancelled_at)}</span>
                        )}
                    </div>
                </div>
            )}

            <div className="relative">
                {normalSteps.map((step, index) => {
                    const stepStatus = getStepStatus(order, step, index, activeStepIndex);
                    const timestamp = order[step.key];
                    const isLast = index === normalSteps.length - 1;

                    return (
                        <div key={step.key} className="flex items-start gap-3 pb-4 last:pb-0">
                            <div className="flex flex-col items-center">
                                <div
                                    className={`w-3 h-3 rounded-full border-2 ${
                                        stepStatus === "completed"
                                            ? "bg-sky-500 border-sky-500"
                                            : stepStatus === "active"
                                              ? "bg-white border-sky-500"
                                              : "bg-white border-edge-strong"
                                    }`}
                                />
                                {!isLast && (
                                    <div
                                        className={`w-0.5 h-8 ${
                                            stepStatus === "completed" ? "bg-sky-500" : "bg-edge"
                                        }`}
                                    />
                                )}
                            </div>
                            <div className="flex-1 min-w-0 -mt-0.5">
                                <div className="flex items-center justify-between">
                                    <span
                                        className={`text-sm font-medium ${
                                            stepStatus === "pending" ? "text-muted-light" : "text-ink"
                                        }`}
                                    >
                                        {step.label}
                                    </span>
                                    {timestamp && (
                                        <span className="text-xs text-muted">{formatTime(timestamp)}</span>
                                    )}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
